<?php

namespace NexusExtensions\GoogleSheets\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use NexusExtensions\GoogleSheets\Http\Requests\GoogleSheetsCreateSpreadsheetRequest;
use NexusExtensions\GoogleSheets\Http\Requests\GoogleSheetsWriteRangeRequest;
use NexusExtensions\GoogleSheets\Services\GoogleSheetsService;
use RuntimeException;
use Throwable;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;

class GoogleSheetsController extends Controller
{
    public function __construct(protected GoogleSheetsService $service)
    {
    }

    // ── Pages ──────────────────────────────────────────────────────────────

    public function index()
    {
        $tenantId        = $this->tenantId();
        $storageReady    = $this->isStorageReady();
        $extensionActive = $storageReady && $this->isExtensionActive($tenantId);
        $token           = ($storageReady && $extensionActive) ? $this->service->getToken($tenantId) : null;

        return view('google-sheets::sheets.index', [
            'storageReady'    => $storageReady,
            'extensionActive' => $extensionActive,
            'connected'       => (bool) $token,
            'token'           => $token,
        ]);
    }

    // ── OAuth ──────────────────────────────────────────────────────────────

    public function connect()
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $authUrl  = $this->service->getAuthUrl($tenantId, (int) Auth::id());

            return redirect()->away($authUrl);
        } catch (Throwable $e) {
            return redirect()->route('google-sheets.index')->with('error', $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('google-sheets.index')
                ->with('error', (string) $request->get('error_description', $request->get('error')));
        }

        $request->validate([
            'code'  => ['required', 'string'],
            'state' => ['required', 'string'],
        ]);

        try {
            $state    = $this->service->parseState((string) $request->string('state'));
            $tenantId = (int) $state['tenant_id'];
            $userId   = (int) $state['user_id'];

            if ((int) Auth::id() !== $userId || (int) Auth::user()->tenant_id !== $tenantId) {
                throw new RuntimeException('État OAuth invalide pour la session en cours.');
            }

            $this->ensureExtensionActivated($tenantId);
            $this->service->exchangeCode((string) $request->string('code'), $tenantId, $userId);

            return redirect()->route('google-sheets.index')
                ->with('success', 'Google Sheets connecté avec succès.');
        } catch (Throwable $e) {
            return redirect()->route('google-sheets.index')->with('error', $e->getMessage());
        }
    }

    public function disconnect(): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $this->service->disconnect($tenantId);

            return response()->json(['success' => true, 'message' => 'Google Sheets déconnecté.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── Spreadsheets ────────────────────────────────────────────────────────

    public function spreadsheetsData(Request $request): JsonResponse
    {
        $request->validate([
            'search'     => ['nullable', 'string', 'max:255'],
            'page_token' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $data = $this->service->listSpreadsheets(
                $tenantId,
                (string) $request->string('search', ''),
                $request->filled('page_token') ? (string) $request->string('page_token') : null
            );

            return response()->json(['success' => true, 'data' => $data]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function showSpreadsheet(string $spreadsheetId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $data = $this->service->getSpreadsheet($tenantId, $spreadsheetId);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function createSpreadsheet(GoogleSheetsCreateSpreadsheetRequest $request): JsonResponse
    {
        try {
            $tenantId    = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $spreadsheet = $this->service->createSpreadsheet(
                $tenantId,
                (string) $request->string('title'),
                (array) ($request->input('sheet_titles', []))
            );

            return response()->json([
                'success' => true,
                'message' => 'Feuille de calcul créée avec succès.',
                'data'    => $spreadsheet,
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function renameSpreadsheet(Request $request, string $spreadsheetId): JsonResponse
    {
        $request->validate(['title' => ['required', 'string', 'max:500']]);

        try {
            $tenantId    = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $spreadsheet = $this->service->renameSpreadsheet($tenantId, $spreadsheetId, (string) $request->string('title'));

            return response()->json(['success' => true, 'message' => 'Feuille de calcul renommée.', 'data' => $spreadsheet]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function deleteSpreadsheet(string $spreadsheetId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $this->service->deleteSpreadsheet($tenantId, $spreadsheetId);

            return response()->json(['success' => true, 'message' => 'Feuille de calcul supprimée.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function duplicateSpreadsheet(Request $request, string $spreadsheetId): JsonResponse
    {
        $request->validate(['title' => ['nullable', 'string', 'max:500']]);

        try {
            $tenantId    = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $spreadsheet = $this->service->duplicateSpreadsheet(
                $tenantId,
                $spreadsheetId,
                (string) $request->string('title', '')
            );

            return response()->json(['success' => true, 'message' => 'Feuille de calcul dupliquée.', 'data' => $spreadsheet]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── Sheets (onglets) ────────────────────────────────────────────────────

    public function addSheet(Request $request, string $spreadsheetId): JsonResponse
    {
        $request->validate(['title' => ['required', 'string', 'max:100']]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $sheet    = $this->service->addSheet($tenantId, $spreadsheetId, (string) $request->string('title'));

            return response()->json(['success' => true, 'message' => 'Onglet ajouté.', 'data' => $sheet], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function renameSheet(Request $request, string $spreadsheetId, int $sheetId): JsonResponse
    {
        $request->validate(['title' => ['required', 'string', 'max:100']]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $this->service->renameSheet($tenantId, $spreadsheetId, $sheetId, (string) $request->string('title'));

            return response()->json(['success' => true, 'message' => 'Onglet renommé.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function deleteSheet(string $spreadsheetId, int $sheetId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $this->service->deleteSheet($tenantId, $spreadsheetId, $sheetId);

            return response()->json(['success' => true, 'message' => 'Onglet supprimé.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── Data (cellules) ─────────────────────────────────────────────────────

    public function readRange(Request $request, string $spreadsheetId): JsonResponse
    {
        $request->validate(['range' => ['required', 'string', 'max:255']]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $data     = $this->service->readRange($tenantId, $spreadsheetId, (string) $request->string('range'));

            return response()->json(['success' => true, 'data' => $data]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function writeRange(GoogleSheetsWriteRangeRequest $request, string $spreadsheetId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $data     = $this->service->writeRange(
                $tenantId,
                $spreadsheetId,
                (string) $request->string('range'),
                (array) $request->input('values')
            );

            return response()->json(['success' => true, 'message' => 'Données écrites.', 'data' => $data]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function appendRows(Request $request, string $spreadsheetId): JsonResponse
    {
        $request->validate([
            'range'    => ['required', 'string', 'max:255'],
            'values'   => ['required', 'array'],
            'values.*' => ['array'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $data     = $this->service->appendRows(
                $tenantId,
                $spreadsheetId,
                (string) $request->string('range'),
                (array) $request->input('values')
            );

            return response()->json(['success' => true, 'message' => 'Lignes ajoutées.', 'data' => $data]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function clearRange(Request $request, string $spreadsheetId): JsonResponse
    {
        $request->validate(['range' => ['required', 'string', 'max:255']]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $this->service->clearRange($tenantId, $spreadsheetId, (string) $request->string('range'));

            return response()->json(['success' => true, 'message' => 'Plage vidée.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function batchRead(Request $request, string $spreadsheetId): JsonResponse
    {
        $request->validate([
            'ranges'   => ['required', 'array', 'min:1', 'max:20'],
            'ranges.*' => ['string', 'max:255'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $data     = $this->service->batchRead($tenantId, $spreadsheetId, (array) $request->input('ranges'));

            return response()->json(['success' => true, 'data' => $data]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function batchWrite(Request $request, string $spreadsheetId): JsonResponse
    {
        $request->validate([
            'data'            => ['required', 'array', 'min:1', 'max:20'],
            'data.*.range'    => ['required', 'string', 'max:255'],
            'data.*.values'   => ['required', 'array'],
            'data.*.values.*' => ['array'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $result   = $this->service->batchWrite($tenantId, $spreadsheetId, (array) $request->input('data'));

            return response()->json(['success' => true, 'message' => 'Écriture groupée terminée.', 'data' => $result]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── Stats ───────────────────────────────────────────────────────────────

    public function stats(): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            return response()->json(['success' => true, 'data' => $this->service->getStats($tenantId)]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function tenantId(): int
    {
        return (int) Auth::user()->tenant_id;
    }

    private function ensureExtensionActivated(int $tenantId): void
    {
        $this->assertStorageReady();
        if (!$this->isExtensionActive($tenantId)) {
            throw new RuntimeException('Google Sheets n’est pas activé pour ce tenant. Activez l’application depuis le Marketplace.');
        }
    }

    private function isExtensionActive(int $tenantId): bool
    {
        $extension = Extension::query()->where('slug', 'google-sheets')->first();
        if (!$extension) return false;

        return TenantExtension::query()
            ->where('tenant_id', $tenantId)
            ->where('extension_id', $extension->id)
            ->whereIn('status', ['active', 'trial'])
            ->exists();
    }

    private function isStorageReady(): bool
    {
        return Schema::hasTable('google_sheets_tokens')
            && Schema::hasTable('google_sheets_spreadsheets')
            && Schema::hasTable('google_sheets_sheets')
            && Schema::hasTable('google_sheets_activity_logs');
    }

    private function assertStorageReady(): void
    {
        if (!$this->isStorageReady()) {
            throw new RuntimeException('Les tables Google Sheets sont absentes. Exécutez: php artisan migrate');
        }
    }
}
