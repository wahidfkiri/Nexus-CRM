<?php

namespace Vendor\GoogleCalendar\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;
use Vendor\GoogleCalendar\Http\Requests\GoogleCalendarEventRequest;
use Vendor\GoogleCalendar\Http\Requests\GoogleCalendarSelectCalendarRequest;
use Vendor\GoogleCalendar\Models\GoogleCalendarCalendar;
use Vendor\GoogleCalendar\Services\GoogleCalendarService;

class GoogleCalendarController extends Controller
{
    public function __construct(protected GoogleCalendarService $service)
    {
    }

    public function index()
    {
        $tenantId = $this->tenantId();
        $storageReady = $this->isStorageReady();
        $extensionActive = $storageReady && $this->isExtensionActive($tenantId);

        $token = ($storageReady && $extensionActive) ? $this->service->getToken($tenantId) : null;

        $calendars = ($storageReady && $extensionActive)
            ? GoogleCalendarCalendar::forTenant($tenantId)
                ->where('is_deleted', false)
                ->orderByDesc('is_selected')
                ->orderByDesc('is_primary')
                ->orderBy('summary')
                ->get()
            : collect();

        return view('google-calendar::calendar.index', [
            'storageReady' => $storageReady,
            'extensionActive' => $extensionActive,
            'connected' => (bool) $token,
            'token' => $token,
            'calendars' => $calendars,
        ]);
    }

    public function connect()
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $authUrl = $this->service->getAuthUrl($tenantId, Auth::id());

            return redirect()->away($authUrl);
        } catch (Throwable $e) {
            return redirect()->route('google-calendar.index')->with('error', $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('google-calendar.index')->with('error', (string) $request->get('error_description', $request->get('error')));
        }

        $request->validate([
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
        ]);

        try {
            $state = $this->service->parseState((string) $request->string('state'));

            $tenantId = (int) $state['tenant_id'];
            $userId = (int) $state['user_id'];

            if ((int) Auth::id() !== $userId || (int) Auth::user()->tenant_id !== $tenantId) {
                throw new RuntimeException('OAuth state does not match current session.');
            }

            $this->ensureExtensionActivated($tenantId);
            $this->service->exchangeCode((string) $request->string('code'), $tenantId, $userId);

            return redirect()->route('google-calendar.index')->with('success', 'Google Calendar connected successfully.');
        } catch (Throwable $e) {
            return redirect()->route('google-calendar.index')->with('error', $e->getMessage());
        }
    }

    public function disconnect(): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $this->service->disconnect($tenantId);

            return response()->json([
                'success' => true,
                'message' => 'Google Calendar disconnected.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function calendarsData(Request $request): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            if ($request->boolean('refresh')) {
                $this->service->syncCalendars($tenantId);
            }

            $calendars = GoogleCalendarCalendar::forTenant($tenantId)
                ->where('is_deleted', false)
                ->orderByDesc('is_selected')
                ->orderByDesc('is_primary')
                ->orderBy('summary')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $calendars,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function selectCalendar(GoogleCalendarSelectCalendarRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $calendar = $this->service->selectCalendar($tenantId, (string) $request->string('calendar_id'));

            return response()->json([
                'success' => true,
                'message' => 'Calendar selected successfully.',
                'data' => $calendar,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function eventsData(Request $request): JsonResponse
    {
        $request->validate([
            'calendar_id' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'refresh' => ['nullable', 'boolean'],
            'include_holidays' => ['nullable', 'boolean'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            if ($request->boolean('refresh')) {
                $from = $request->filled('from') ? Carbon::parse((string) $request->string('from'))->startOfDay() : null;
                $to = $request->filled('to') ? Carbon::parse((string) $request->string('to'))->endOfDay() : null;

                $this->service->syncEvents(
                    $tenantId,
                    $request->filled('calendar_id') ? (string) $request->string('calendar_id') : null,
                    $from,
                    $to,
                    $request->boolean('include_holidays', true)
                );
            }

            $events = $this->service->getLocalEvents($tenantId, $request->all());

            return response()->json([
                'success' => true,
                'data' => $events->getCollection()->map(fn ($event) => $this->service->formatEvent($event))->values(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'from' => $events->firstItem(),
                'to' => $events->lastItem(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            return response()->json([
                'success' => true,
                'data' => $this->service->getStats($tenantId),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'calendar_id' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'include_holidays' => ['nullable', 'boolean'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $this->service->syncCalendars($tenantId);

            $count = $this->service->syncEvents(
                $tenantId,
                $request->filled('calendar_id') ? (string) $request->string('calendar_id') : null,
                $request->filled('from') ? Carbon::parse((string) $request->string('from'))->startOfDay() : null,
                $request->filled('to') ? Carbon::parse((string) $request->string('to'))->endOfDay() : null,
                $request->boolean('include_holidays', true)
            );

            return response()->json([
                'success' => true,
                'message' => $count . ' events synchronized.',
                'count' => $count,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function storeEvent(GoogleCalendarEventRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $event = $this->service->createEvent($tenantId, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Event created successfully.',
                'data' => $event,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function updateEvent(GoogleCalendarEventRequest $request, string $calendarId, string $eventId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $payload = $request->validated();
            $payload['calendar_id'] = $calendarId;

            $event = $this->service->updateEvent($tenantId, $calendarId, $eventId, $payload);

            return response()->json([
                'success' => true,
                'message' => 'Event updated successfully.',
                'data' => $event,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroyEvent(string $calendarId, string $eventId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $this->service->deleteEvent($tenantId, $calendarId, $eventId);

            return response()->json([
                'success' => true,
                'message' => 'Event deleted.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function tenantId(): int
    {
        return (int) Auth::user()->tenant_id;
    }

    private function ensureExtensionActivated(int $tenantId): void
    {
        $this->assertStorageReady();

        if (!$this->isExtensionActive($tenantId)) {
            throw new RuntimeException('Google Calendar extension is not active for this tenant. Activate it from Marketplace first.');
        }
    }

    private function isExtensionActive(int $tenantId): bool
    {
        $slug = (string) config('google-calendar.slug', 'google-calendar');

        $extension = Extension::where('slug', $slug)->first();
        if (!$extension) {
            return false;
        }

        return TenantExtension::query()
            ->where('tenant_id', $tenantId)
            ->where('extension_id', $extension->id)
            ->whereIn('status', ['active', 'trial'])
            ->exists();
    }

    private function isStorageReady(): bool
    {
        return Schema::hasTable('google_calendar_tokens')
            && Schema::hasTable('google_calendar_calendars')
            && Schema::hasTable('google_calendar_events')
            && Schema::hasTable('google_calendar_activity_logs');
    }

    private function assertStorageReady(): void
    {
        if (!$this->isStorageReady()) {
            throw new RuntimeException('Google Calendar tables are missing. Run migrations: php artisan migrate');
        }
    }
}
