<?php

namespace NexusExtensions\GoogleSheets\Services;

use Google\Client as GoogleClient;
use Google\Service\Drive;
use Google\Service\Sheets;
use Google\Service\Sheets\Spreadsheet;
use Google\Service\Sheets\Sheet;
use Google\Service\Sheets\SheetProperties;
use Google\Service\Sheets\GridProperties;
use Google\Service\Sheets\AddSheetRequest;
use Google\Service\Sheets\DeleteSheetRequest;
use Google\Service\Sheets\UpdateSheetPropertiesRequest;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\Request as SheetsRequest;
use Google\Service\Sheets\ValueRange;
use Google\Service\Sheets\ClearValuesRequest;
use Google\Service\Oauth2;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use NexusExtensions\GoogleSheets\Models\GoogleSheetsActivityLog;
use NexusExtensions\GoogleSheets\Models\GoogleSheetsSpreadsheet;
use NexusExtensions\GoogleSheets\Models\GoogleSheetsSheet;
use NexusExtensions\GoogleSheets\Models\GoogleSheetsToken;
use RuntimeException;

class GoogleSheetsService
{
    private ?GoogleClient $client = null;
    private ?Sheets $sheetsService = null;
    private ?Drive $driveService = null;

    // ── OAuth ───────────────────────────────────────────────────────────────

    public function makeClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId((string) config('google-sheets.oauth.client_id'));
        $client->setClientSecret((string) config('google-sheets.oauth.client_secret'));
        $client->setRedirectUri($this->redirectUri());
        $client->setScopes((array) config('google-sheets.oauth.scopes', []));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);

        return $client;
    }

    public function getAuthUrl(int $tenantId, int $userId): string
    {
        $clientId = (string) config('google-sheets.oauth.client_id');
        if ($clientId === '') {
            throw new RuntimeException('GOOGLE_SHEETS_CLIENT_ID is missing.');
        }

        $client = $this->makeClient();
        $state  = encrypt([
            'tenant_id' => $tenantId,
            'user_id'   => $userId,
            'nonce'     => Str::uuid()->toString(),
            'ts'        => now()->timestamp,
        ]);
        $client->setState($state);

        return $client->createAuthUrl();
    }

    public function parseState(string $encryptedState): array
    {
        $state = decrypt($encryptedState);
        if (!is_array($state) || !isset($state['tenant_id'], $state['user_id'])) {
            throw new RuntimeException('Invalid OAuth state.');
        }
        return $state;
    }

    public function exchangeCode(string $code, int $tenantId, int $userId): GoogleSheetsToken
    {
        $client    = $this->makeClient();
        $tokenData = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($tokenData['error'])) {
            throw new RuntimeException((string) ($tokenData['error_description'] ?? $tokenData['error']));
        }

        $client->setAccessToken($tokenData);
        $oauth2   = new Oauth2($client);
        $userInfo = $oauth2->userinfo->get();

        $token = GoogleSheetsToken::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'connected_by'     => $userId,
                'access_token'     => $tokenData['access_token'] ?? null,
                'refresh_token'    => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => isset($tokenData['expires_in'])
                    ? now()->addSeconds((int) $tokenData['expires_in'])
                    : now()->addHour(),
                'google_account_id' => $userInfo->getId(),
                'google_email'      => $userInfo->getEmail(),
                'google_name'       => $userInfo->getName(),
                'google_avatar_url' => $userInfo->getPicture(),
                'is_active'         => true,
                'connected_at'      => now(),
                'disconnected_at'   => null,
            ]
        );

        $this->client       = $client;
        $this->sheetsService = new Sheets($client);
        $this->driveService  = new Drive($client);

        $this->log($tenantId, 'connected', null, null, null, ['google_email' => $userInfo->getEmail()]);

        return $token->fresh();
    }

    public function disconnect(int $tenantId): void
    {
        $token = GoogleSheetsToken::forTenant($tenantId)->first();
        if (!$token) return;

        try {
            $client = $this->makeClient();
            if ($token->access_token) {
                $client->revokeToken($token->access_token);
            }
        } catch (\Throwable $e) {
            Log::warning('[GoogleSheets] token revoke failed', ['message' => $e->getMessage()]);
        }

        $token->update([
            'is_active'       => false,
            'disconnected_at' => now(),
            'access_token'    => null,
            'refresh_token'   => null,
        ]);

        $this->log($tenantId, 'disconnected');
    }

    public function getToken(int $tenantId): ?GoogleSheetsToken
    {
        return GoogleSheetsToken::forTenant($tenantId)->active()->first();
    }

    // ── Client Google ────────────────────────────────────────────────────────

    public function getSheetsService(int $tenantId): Sheets
    {
        if ($this->sheetsService) return $this->sheetsService;

        $token  = $this->getValidToken($tenantId);
        $client = $this->makeClient();
        $client->setAccessToken($token->toGoogleToken());

        if ($token->is_expired && $token->refresh_token) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($token->refresh_token);
            if (!isset($newToken['error'])) {
                $token->update([
                    'access_token'     => $newToken['access_token'] ?? $token->access_token,
                    'token_expires_at' => now()->addSeconds((int) ($newToken['expires_in'] ?? 3600)),
                ]);
                $client->setAccessToken($newToken);
            } else {
                throw new RuntimeException((string) ($newToken['error_description'] ?? $newToken['error']));
            }
        }

        $this->client        = $client;
        $this->sheetsService = new Sheets($client);
        $this->driveService  = new Drive($client);

        return $this->sheetsService;
    }

    public function getDriveService(int $tenantId): Drive
    {
        if ($this->driveService) return $this->driveService;
        $this->getSheetsService($tenantId);
        return $this->driveService;
    }

    // ── Spreadsheets ─────────────────────────────────────────────────────────

    public function listSpreadsheets(int $tenantId, string $search = '', ?string $pageToken = null): array
    {
        $drive = $this->getDriveService($tenantId);

        $query = "mimeType='application/vnd.google-apps.spreadsheet' and trashed = false";
        if ($search !== '') {
            $escaped = addslashes($search);
            $query   = "mimeType='application/vnd.google-apps.spreadsheet' and name contains '{$escaped}' and trashed = false";
        }

        $params = [
            'q'        => $query,
            'fields'   => 'nextPageToken,files(id,name,createdTime,modifiedTime,webViewLink,shared)',
            'pageSize' => (int) config('google-sheets.api.page_size', 50),
            'orderBy'  => 'modifiedTime desc',
        ];

        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        $result  = $drive->files->listFiles($params);
        $sheets  = [];

        foreach ((array) $result->getFiles() as $file) {
            $sheets[] = $this->formatSpreadsheetFromDrive($file);
            $this->syncSpreadsheetToLocal($file, $tenantId);
        }

        return [
            'spreadsheets'   => $sheets,
            'next_page_token' => $result->getNextPageToken(),
        ];
    }

    public function getSpreadsheet(int $tenantId, string $spreadsheetId): array
    {
        $service     = $this->getSheetsService($tenantId);
        $spreadsheet = $service->spreadsheets->get($spreadsheetId, [
            'includeGridData' => false,
        ]);

        $this->syncSpreadsheetFull($spreadsheet, $tenantId);

        return $this->formatSpreadsheet($spreadsheet);
    }

    public function createSpreadsheet(int $tenantId, string $title, array $sheetTitles = []): array
    {
        $service = $this->getSheetsService($tenantId);

        $sheetsData = [];
        if (empty($sheetTitles)) {
            $sheetTitles = ['Sheet1'];
        }

        foreach ($sheetTitles as $idx => $sheetTitle) {
            $sheetsData[] = new Sheet([
                'properties' => new SheetProperties([
                    'title'      => $sheetTitle,
                    'index'      => $idx,
                    'sheetType'  => 'GRID',
                    'gridProperties' => new GridProperties([
                        'rowCount'    => 1000,
                        'columnCount' => 26,
                    ]),
                ]),
            ]);
        }

        $spreadsheet = $service->spreadsheets->create(new Spreadsheet([
            'properties' => ['title' => $title],
            'sheets'     => $sheetsData,
        ]), ['fields' => 'spreadsheetId,spreadsheetUrl,properties,sheets']);

        $this->syncSpreadsheetFull($spreadsheet, $tenantId);
        $this->log($tenantId, 'create_spreadsheet', $spreadsheet->getSpreadsheetId(), $title);

        return $this->formatSpreadsheet($spreadsheet);
    }

    public function renameSpreadsheet(int $tenantId, string $spreadsheetId, string $newTitle): array
    {
        $service = $this->getSheetsService($tenantId);

        $request = new SheetsRequest([
            'updateSpreadsheetProperties' => [
                'properties' => ['title' => $newTitle],
                'fields'     => 'title',
            ],
        ]);

        $service->spreadsheets->batchUpdate(
            $spreadsheetId,
            new BatchUpdateSpreadsheetRequest(['requests' => [$request]])
        );

        GoogleSheetsSpreadsheet::forTenant($tenantId)
            ->where('spreadsheet_id', $spreadsheetId)
            ->update(['title' => $newTitle, 'modified_by' => Auth::id()]);

        $this->log($tenantId, 'rename_spreadsheet', $spreadsheetId, $newTitle);

        return $this->getSpreadsheet($tenantId, $spreadsheetId);
    }

    public function deleteSpreadsheet(int $tenantId, string $spreadsheetId): bool
    {
        $drive = $this->getDriveService($tenantId);
        $local = GoogleSheetsSpreadsheet::forTenant($tenantId)
            ->where('spreadsheet_id', $spreadsheetId)
            ->first();

        $title = $local?->title ?? $spreadsheetId;
        $drive->files->delete($spreadsheetId);

        if ($local) {
            $local->sheets()->delete();
            $local->delete();
        }

        $this->log($tenantId, 'delete_spreadsheet', $spreadsheetId, $title);

        return true;
    }

    public function duplicateSpreadsheet(int $tenantId, string $spreadsheetId, string $newTitle = ''): array
    {
        $drive = $this->getDriveService($tenantId);
        $local = GoogleSheetsSpreadsheet::forTenant($tenantId)
            ->where('spreadsheet_id', $spreadsheetId)
            ->first();

        $title = $newTitle !== '' ? $newTitle : ('Copy of ' . ($local?->title ?? $spreadsheetId));

        $copied = $drive->files->copy($spreadsheetId, new Drive\DriveFile(['name' => $title]), [
            'fields' => 'id,name,createdTime,modifiedTime,webViewLink,shared',
        ]);

        $this->syncSpreadsheetToLocal($copied, $tenantId);
        $this->log($tenantId, 'duplicate_spreadsheet', $copied->getId(), $title);

        return $this->getSpreadsheet($tenantId, $copied->getId());
    }

    // ── Sheets (onglets) ──────────────────────────────────────────────────────

    public function addSheet(int $tenantId, string $spreadsheetId, string $title): array
    {
        $service = $this->getSheetsService($tenantId);

        $request = new SheetsRequest([
            'addSheet' => new AddSheetRequest([
                'properties' => new SheetProperties([
                    'title' => $title,
                    'gridProperties' => new GridProperties([
                        'rowCount'    => 1000,
                        'columnCount' => 26,
                    ]),
                ]),
            ]),
        ]);

        $response = $service->spreadsheets->batchUpdate(
            $spreadsheetId,
            new BatchUpdateSpreadsheetRequest(['requests' => [$request]])
        );

        $replies   = $response->getReplies();
        $sheetProp = $replies[0]->getAddSheet()->getProperties();

        $this->log($tenantId, 'add_sheet', $spreadsheetId, null, $title);

        return [
            'sheet_id' => $sheetProp->getSheetId(),
            'title'    => $sheetProp->getTitle(),
            'index'    => $sheetProp->getIndex(),
        ];
    }

    public function renameSheet(int $tenantId, string $spreadsheetId, int $sheetId, string $newTitle): bool
    {
        $service = $this->getSheetsService($tenantId);

        $request = new SheetsRequest([
            'updateSheetProperties' => new UpdateSheetPropertiesRequest([
                'properties' => new SheetProperties([
                    'sheetId' => $sheetId,
                    'title'   => $newTitle,
                ]),
                'fields' => 'title',
            ]),
        ]);

        $service->spreadsheets->batchUpdate(
            $spreadsheetId,
            new BatchUpdateSpreadsheetRequest(['requests' => [$request]])
        );

        GoogleSheetsSheet::forTenant($tenantId)
            ->where('spreadsheet_id', $spreadsheetId)
            ->where('sheet_id', $sheetId)
            ->update(['title' => $newTitle]);

        $this->log($tenantId, 'rename_sheet', $spreadsheetId, null, $newTitle);

        return true;
    }

    public function deleteSheet(int $tenantId, string $spreadsheetId, int $sheetId): bool
    {
        $service = $this->getSheetsService($tenantId);

        $request = new SheetsRequest([
            'deleteSheet' => new DeleteSheetRequest(['sheetId' => $sheetId]),
        ]);

        $service->spreadsheets->batchUpdate(
            $spreadsheetId,
            new BatchUpdateSpreadsheetRequest(['requests' => [$request]])
        );

        GoogleSheetsSheet::forTenant($tenantId)
            ->where('spreadsheet_id', $spreadsheetId)
            ->where('sheet_id', $sheetId)
            ->delete();

        $this->log($tenantId, 'delete_sheet', $spreadsheetId, null, null);

        return true;
    }

    // ── Data (cellules / plages) ───────────────────────────────────────────────

    public function readRange(int $tenantId, string $spreadsheetId, string $range): array
    {
        $service  = $this->getSheetsService($tenantId);
        $response = $service->spreadsheets_values->get($spreadsheetId, $range, [
            'valueRenderOption'     => 'FORMATTED_VALUE',
            'dateTimeRenderOption'  => 'FORMATTED_STRING',
        ]);

        $values = $response->getValues() ?? [];
        $this->log($tenantId, 'read_range', $spreadsheetId, null, null, ['range' => $range]);

        return [
            'range'  => $response->getRange(),
            'values' => $values,
            'rows'   => count($values),
            'cols'   => count($values[0] ?? []),
        ];
    }

    public function writeRange(int $tenantId, string $spreadsheetId, string $range, array $values): array
    {
        $service = $this->getSheetsService($tenantId);

        $body     = new ValueRange(['values' => $values]);
        $params   = ['valueInputOption' => config('google-sheets.value_input_option', 'USER_ENTERED')];
        $response = $service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);

        $this->log($tenantId, 'write_range', $spreadsheetId, null, null, [
            'range'           => $range,
            'updatedCells'    => $response->getUpdatedCells(),
            'updatedRows'     => $response->getUpdatedRows(),
            'updatedColumns'  => $response->getUpdatedColumns(),
        ]);

        return [
            'updated_range'   => $response->getUpdatedRange(),
            'updated_rows'    => $response->getUpdatedRows(),
            'updated_columns' => $response->getUpdatedColumns(),
            'updated_cells'   => $response->getUpdatedCells(),
        ];
    }

    public function appendRows(int $tenantId, string $spreadsheetId, string $range, array $values): array
    {
        $service = $this->getSheetsService($tenantId);

        $body     = new ValueRange(['values' => $values]);
        $params   = [
            'valueInputOption'  => config('google-sheets.value_input_option', 'USER_ENTERED'),
            'insertDataOption'  => 'INSERT_ROWS',
        ];
        $response = $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
        $updates  = $response->getUpdates();

        $this->log($tenantId, 'append_rows', $spreadsheetId, null, null, [
            'range' => $range,
            'rows'  => count($values),
        ]);

        return [
            'updated_range'   => $updates?->getUpdatedRange(),
            'updated_rows'    => $updates?->getUpdatedRows(),
            'updated_cells'   => $updates?->getUpdatedCells(),
        ];
    }

    public function clearRange(int $tenantId, string $spreadsheetId, string $range): bool
    {
        $service = $this->getSheetsService($tenantId);
        $service->spreadsheets_values->clear($spreadsheetId, $range, new ClearValuesRequest());
        $this->log($tenantId, 'clear_range', $spreadsheetId, null, null, ['range' => $range]);

        return true;
    }

    public function batchRead(int $tenantId, string $spreadsheetId, array $ranges): array
    {
        $service  = $this->getSheetsService($tenantId);
        $response = $service->spreadsheets_values->batchGet($spreadsheetId, [
            'ranges'                => $ranges,
            'valueRenderOption'     => 'FORMATTED_VALUE',
            'dateTimeRenderOption'  => 'FORMATTED_STRING',
        ]);

        $result = [];
        foreach ($response->getValueRanges() as $vr) {
            $result[] = [
                'range'  => $vr->getRange(),
                'values' => $vr->getValues() ?? [],
            ];
        }

        $this->log($tenantId, 'batch_read', $spreadsheetId, null, null, ['ranges' => $ranges]);

        return $result;
    }

    public function batchWrite(int $tenantId, string $spreadsheetId, array $data): array
    {
        $service = $this->getSheetsService($tenantId);

        $valueRanges = [];
        foreach ($data as $item) {
            $valueRanges[] = new ValueRange([
                'range'  => $item['range'],
                'values' => $item['values'],
            ]);
        }

        $body = new \Google\Service\Sheets\BatchUpdateValuesRequest([
            'valueInputOption' => config('google-sheets.value_input_option', 'USER_ENTERED'),
            'data'             => $valueRanges,
        ]);

        $response = $service->spreadsheets_values->batchUpdate($spreadsheetId, $body);

        $this->log($tenantId, 'batch_write', $spreadsheetId, null, null, [
            'ranges'       => count($data),
            'updatedCells' => $response->getTotalUpdatedCells(),
        ]);

        return [
            'total_updated_cells'   => $response->getTotalUpdatedCells(),
            'total_updated_rows'    => $response->getTotalUpdatedRows(),
            'total_updated_columns' => $response->getTotalUpdatedColumns(),
            'total_updated_sheets'  => $response->getTotalUpdatedSheets(),
        ];
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    public function getStats(int $tenantId): array
    {
        $token = $this->getToken($tenantId);

        return [
            'connected'             => (bool) $token,
            'google_email'          => $token?->google_email,
            'google_name'           => $token?->google_name,
            'connected_at'          => $token?->connected_at?->toIso8601String(),
            'last_sync_at'          => $token?->last_sync_at?->toIso8601String(),
            'total_spreadsheets'    => GoogleSheetsSpreadsheet::forTenant($tenantId)->count(),
            'total_sheets'          => GoogleSheetsSheet::forTenant($tenantId)->count(),
        ];
    }

    // ── Sync local ────────────────────────────────────────────────────────────

    private function syncSpreadsheetToLocal($file, int $tenantId): void
    {
        try {
            GoogleSheetsSpreadsheet::updateOrCreate(
                ['tenant_id' => $tenantId, 'spreadsheet_id' => $file->getId()],
                [
                    'title'              => $file->getName(),
                    'spreadsheet_url'    => $file->getWebViewLink(),
                    'is_shared'          => (bool) $file->getShared(),
                    'drive_created_at'   => $file->getCreatedTime(),
                    'drive_modified_at'  => $file->getModifiedTime(),
                    'modified_by'        => Auth::id(),
                    'created_by'         => Auth::id(),
                ]
            );
        } catch (\Throwable $e) {
            Log::debug('[GoogleSheets] local sync skipped', ['message' => $e->getMessage()]);
        }
    }

    private function syncSpreadsheetFull(Spreadsheet $spreadsheet, int $tenantId): void
    {
        try {
            $props = $spreadsheet->getProperties();
            $local = GoogleSheetsSpreadsheet::updateOrCreate(
                ['tenant_id' => $tenantId, 'spreadsheet_id' => $spreadsheet->getSpreadsheetId()],
                [
                    'title'           => $props->getTitle(),
                    'locale'          => $props->getLocale(),
                    'timezone'        => $props->getTimeZone(),
                    'spreadsheet_url' => $spreadsheet->getSpreadsheetUrl(),
                    'modified_by'     => Auth::id(),
                    'created_by'      => Auth::id(),
                ]
            );

            // Sync sheets
            foreach ((array) $spreadsheet->getSheets() as $sheet) {
                $sp = $sheet->getProperties();
                GoogleSheetsSheet::updateOrCreate(
                    [
                        'tenant_id'          => $tenantId,
                        'spreadsheet_id'     => $spreadsheet->getSpreadsheetId(),
                        'sheet_id'           => $sp->getSheetId(),
                    ],
                    [
                        'spreadsheet_local_id' => $local->id,
                        'title'                => $sp->getTitle(),
                        'index'                => $sp->getIndex(),
                        'sheet_type'           => $sp->getSheetType() ?? 'GRID',
                        'row_count'            => $sp->getGridProperties()?->getRowCount() ?? 1000,
                        'column_count'         => $sp->getGridProperties()?->getColumnCount() ?? 26,
                        'hidden'               => (bool) $sp->getHidden(),
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::debug('[GoogleSheets] full sync skipped', ['message' => $e->getMessage()]);
        }
    }

    // ── Formatters ────────────────────────────────────────────────────────────

    private function formatSpreadsheet(Spreadsheet $spreadsheet): array
    {
        $props  = $spreadsheet->getProperties();
        $sheets = [];

        foreach ((array) $spreadsheet->getSheets() as $sheet) {
            $sp       = $sheet->getProperties();
            $grid     = $sp->getGridProperties();
            $sheets[] = [
                'sheet_id'     => $sp->getSheetId(),
                'title'        => $sp->getTitle(),
                'index'        => $sp->getIndex(),
                'sheet_type'   => $sp->getSheetType() ?? 'GRID',
                'row_count'    => $grid?->getRowCount() ?? 1000,
                'column_count' => $grid?->getColumnCount() ?? 26,
                'hidden'       => (bool) $sp->getHidden(),
            ];
        }

        return [
            'spreadsheet_id'  => $spreadsheet->getSpreadsheetId(),
            'title'           => $props->getTitle(),
            'locale'          => $props->getLocale(),
            'timezone'        => $props->getTimeZone(),
            'spreadsheet_url' => $spreadsheet->getSpreadsheetUrl(),
            'sheets'          => $sheets,
            'sheets_count'    => count($sheets),
        ];
    }

    private function formatSpreadsheetFromDrive($file): array
    {
        return [
            'spreadsheet_id'  => $file->getId(),
            'title'           => $file->getName(),
            'spreadsheet_url' => $file->getWebViewLink(),
            'is_shared'       => (bool) $file->getShared(),
            'created_at'      => $file->getCreatedTime(),
            'modified_at'     => $file->getModifiedTime(),
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getValidToken(int $tenantId): GoogleSheetsToken
    {
        $token = $this->getToken($tenantId);
        if (!$token) {
            throw new RuntimeException('Google Sheets is not connected for this tenant.');
        }
        return $token;
    }

    private function redirectUri(): string
    {
        $path = (string) config('google-sheets.oauth.redirect_uri', '/extensions/google-sheets/oauth/callback');
        if (trim($path) === '') {
            $path = '/extensions/google-sheets/oauth/callback';
        }
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }
        return url($path);
    }

    private function log(
        int $tenantId,
        string $action,
        ?string $spreadsheetId = null,
        ?string $spreadsheetTitle = null,
        ?string $sheetTitle = null,
        array $metadata = []
    ): void {
        try {
            GoogleSheetsActivityLog::create([
                'tenant_id'         => $tenantId,
                'user_id'           => Auth::id(),
                'spreadsheet_id'    => $spreadsheetId,
                'spreadsheet_title' => $spreadsheetTitle,
                'sheet_title'       => $sheetTitle,
                'action'            => $action,
                'metadata'          => $metadata,
                'ip_address'        => request()?->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::debug('[GoogleSheets] activity log skipped', ['message' => $e->getMessage()]);
        }
    }
}