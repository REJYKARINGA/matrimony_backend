<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Festival;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class FestivalController extends Controller
{
    public function index()
    {
        $festivals = Festival::with('occurrences')->orderBy('created_at', 'desc')->get();
        return response()->json(['festivals' => $festivals]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'celebration_name' => 'required|string|max:255',
            'offer_discount' => 'nullable|numeric|min:0',
            'offer_discount_type' => 'nullable|string|in:cash,percentage',
            'calendar_type' => 'required|string|in:gregorian_fixed,hijri,malayalam',
            'hijri_event' => 'nullable|string|required_if:calendar_type,hijri',
            'ml_event' => 'nullable|string|required_if:calendar_type,malayalam',
            'fixed_month' => 'nullable|integer|min:1|max:12|required_if:calendar_type,gregorian_fixed',
            'fixed_day' => 'nullable|integer|min:1|max:31|required_if:calendar_type,gregorian_fixed',
            'start_offset_days' => 'nullable|integer',
            'end_offset_days' => 'nullable|integer',
            'reminder_days_before' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        $festival = Festival::create($validator->validated());

        return response()->json([
            'message' => 'Festival created successfully',
            'festival' => $festival->load('occurrences'),
        ]);
    }

    public function show(Festival $festival)
    {
        return response()->json(['festival' => $festival->load('occurrences')]);
    }

    public function update(Request $request, Festival $festival)
    {
        $validator = Validator::make($request->all(), [
            'celebration_name' => 'sometimes|string|max:255',
            'offer_discount' => 'nullable|numeric|min:0',
            'offer_discount_type' => 'nullable|string|in:cash,percentage',
            'calendar_type' => 'sometimes|string|in:gregorian_fixed,hijri,malayalam',
            'hijri_event' => 'nullable|string|required_if:calendar_type,hijri',
            'ml_event' => 'nullable|string|required_if:calendar_type,malayalam',
            'fixed_month' => 'nullable|integer|min:1|max:12|required_if:calendar_type,gregorian_fixed',
            'fixed_day' => 'nullable|integer|min:1|max:31|required_if:calendar_type,gregorian_fixed',
            'start_offset_days' => 'nullable|integer',
            'end_offset_days' => 'nullable|integer',
            'reminder_days_before' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        $festival->update($validator->validated());

        return response()->json([
            'message' => 'Festival updated successfully',
            'festival' => $festival->fresh()->load('occurrences'),
        ]);
    }

    public function destroy(Festival $festival)
    {
        $festival->delete();
        return response()->json(['message' => 'Festival deleted successfully']);
    }

    public function resolveYear(Festival $festival, ?int $year = null)
    {
        $year = $year ?? now()->year;
        $occurrence = $festival->ensureOccurrenceForYear($year);

        if (!$occurrence) {
            return response()->json([
                'error' => 'Could not resolve dates for this festival in year ' . $year,
            ], 400);
        }

        return response()->json([
            'message' => 'Dates resolved successfully',
            'occurrence' => $occurrence,
        ]);
    }

    public function resolveAll()
    {
        $festivals = Festival::active()->get();
        $results = [];

        foreach ($festivals as $festival) {
            $occurrence = $festival->ensureOccurrenceForYear(now()->year);
            $results[] = [
                'festival_id' => $festival->id,
                'celebration_name' => $festival->celebration_name,
                'occurrence' => $occurrence,
            ];
        }

        return response()->json([
            'message' => 'All festivals resolved',
            'results' => $results,
        ]);
    }

    protected function getHijriEventMap(): array
    {
        return [
            ['month' => 1, 'day' => 1,  'name' => 'Islamic New Year'],
            ['month' => 1, 'day' => 10, 'name' => 'Day of Ashura'],
            ['month' => 3, 'day' => 12, 'name' => 'Mawlid (Birth of Prophet Muhammad)'],
            ['month' => 7, 'day' => 27, 'name' => 'Isra and Miraj'],
            ['month' => 8, 'day' => 15, 'name' => 'Shab-e-Barat (Mid-Shaban)'],
            ['month' => 9, 'day' => 1,  'name' => 'Ramadan Start'],
            ['month' => 9, 'day' => 21, 'name' => 'Laylat al-Qadr (Night 21)'],
            ['month' => 9, 'day' => 23, 'name' => 'Laylat al-Qadr (Night 23)'],
            ['month' => 9, 'day' => 25, 'name' => 'Laylat al-Qadr (Night 25)'],
            ['month' => 9, 'day' => 27, 'name' => 'Laylat al-Qadr (Night 27)'],
            ['month' => 9, 'day' => 29, 'name' => 'Laylat al-Qadr (Night 29)'],
            ['month' => 10, 'day' => 1,  'name' => 'Eid-ul-Fitr'],
            ['month' => 10, 'day' => 2,  'name' => 'Eid-ul-Fitr (Day 2)'],
            ['month' => 10, 'day' => 3,  'name' => 'Eid-ul-Fitr (Day 3)'],
            ['month' => 12, 'day' => 9,  'name' => 'Day of Arafat'],
            ['month' => 12, 'day' => 10, 'name' => 'Eid-ul-Adha'],
            ['month' => 12, 'day' => 11, 'name' => 'Eid-ul-Adha (Day 2)'],
            ['month' => 12, 'day' => 12, 'name' => 'Eid-ul-Adha (Day 3)'],
            ['month' => 12, 'day' => 13, 'name' => 'Eid-ul-Adha (Day 4)'],
        ];
    }

    protected function getEnglishEventMap(): array
    {
        return [
            ['month' => 1,  'day' => 1,   'name' => 'New Year\'s Day'],
            ['month' => 1,  'day' => 26,  'name' => 'Republic Day (India)'],
            ['month' => 2,  'day' => 14,  'name' => 'Valentine\'s Day'],
            ['month' => 3,  'day' => 8,   'name' => 'International Women\'s Day'],
            ['month' => 3,  'day' => 22,  'name' => 'World Water Day'],
            ['month' => 4,  'day' => 14,  'name' => 'Vishu / Baisakhi / Tamil New Year'],
            ['month' => 4,  'day' => 22,  'name' => 'Earth Day'],
            ['month' => 5,  'day' => 1,   'name' => 'Labour Day'],
            ['month' => 6,  'day' => 5,   'name' => 'World Environment Day'],
            ['month' => 6,  'day' => 21,  'name' => 'International Yoga Day'],
            ['month' => 7,  'day' => 4,   'name' => 'Independence Day (USA)'],
            ['month' => 8,  'day' => 15,  'name' => 'Independence Day (India)'],
            ['month' => 9,  'day' => 5,   'name' => 'Teachers\' Day (India)'],
            ['month' => 10, 'day' => 2,   'name' => 'Gandhi Jayanti'],
            ['month' => 10, 'day' => 31,  'name' => 'Halloween'],
            ['month' => 11, 'day' => 1,   'name' => 'Kerala Piravi (Kerala Formation Day)'],
            ['month' => 11, 'day' => 14,  'name' => 'Children\'s Day (India)'],
            ['month' => 12, 'day' => 25,  'name' => 'Christmas'],
            ['month' => 12, 'day' => 31,  'name' => 'New Year\'s Eve'],
        ];
    }

    public function lookupMonth(Request $request)
    {
        $request->validate([
            'year'  => 'required|integer|min:2020|max:2050',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $year = (int) $request->input('year');
        $month = (int) $request->input('month');

        $celebrations = []; // dateStr => ['events' => [...], 'calendar' => '...']

        // 1) English Calendar events (fixed dates, no API needed)
        foreach ($this->getEnglishEventMap() as $ev) {
            if ($ev['month'] === $month) {
                $day = $ev['day'];
                $dateKey = sprintf('%04d-%02d-%02d', $year, $month, $day);
                if (!isset($celebrations[$dateKey])) {
                    $celebrations[$dateKey] = ['events' => [], 'calendar' => 'english'];
                }
                $celebrations[$dateKey]['events'][] = $ev['name'];
            }
        }

        // 2) Hijri events via AlAdhan calendar API (single call for full month)
        try {
            $resp = Http::timeout(15)->get("https://api.aladhan.com/v1/gToHCalendar/{$month}/{$year}");
            if ($resp->successful()) {
                $data = $resp->json();
                $hijriEventMap = $this->getHijriEventMap();
                foreach (($data['data'] ?? []) as $entry) {
                    $gregDate = $entry['gregorian']['date'] ?? null;
                    $hijriDate = $entry['hijri']['date'] ?? null;
                    if (!$gregDate || !$hijriDate) continue;
                    $parts = explode('-', $gregDate);
                    if (count($parts) !== 3) continue;
                    $dateKey = "{$parts[2]}-{$parts[1]}-{$parts[0]}";
                    $hParts = explode('-', $hijriDate);
                    if (count($hParts) !== 3) continue;
                    $hMonth = (int) $hParts[1];
                    $hDay = (int) $hParts[0];
                    $hDisplay = "{$hParts[0]} {$entry['hijri']['month']['en']} {$hParts[2]} AH";

                    // Check for known Hijri events
                    foreach ($hijriEventMap as $ev) {
                        if ($ev['month'] === $hMonth && $ev['day'] === $hDay) {
                            if (!isset($celebrations[$dateKey])) {
                                $celebrations[$dateKey] = ['events' => [], 'calendar' => 'hijri'];
                            }
                            $celebrations[$dateKey]['events'][] = $ev['name'];
                            $celebrations[$dateKey]['hijri'] = $hDisplay;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::warning("Hijri month lookup failed: {$e->getMessage()}");
        }

        // 3) Malayalam events via ml-panchangam API
        $mlEndpoints = [
            ['url' => "https://ml-panchangam.api.divineapi.com/api/tools/upcoming-events", 'params' => ['count' => 200]],
            ['url' => "https://panchangam.api.divineapi.com/api/tools/upcoming-events", 'params' => ['count' => 200]],
        ];
        foreach ($mlEndpoints as $ep) {
            try {
                $mlResp = Http::timeout(10)->get($ep['url'], $ep['params']);
                if ($mlResp->successful()) {
                    $mlEvents = $mlResp->json();
                    if (is_array($mlEvents)) {
                        foreach ($mlEvents as $me) {
                            if (isset($me['date']) && isset($me['event'])) {
                                $meDate = \Carbon\Carbon::parse($me['date']);
                                if ($meDate->year === $year && $meDate->month === $month) {
                                    $dateKey = $meDate->format('Y-m-d');
                                    if (!isset($celebrations[$dateKey])) {
                                        $celebrations[$dateKey] = ['events' => [], 'calendar' => 'malayalam'];
                                    }
                                    $celebrations[$dateKey]['events'][] = $me['event'];
                                    $celebrations[$dateKey]['calendar'] = 'malayalam';
                                }
                            }
                        }
                    }
                    break;
                }
            } catch (\Exception $e) {
                \Log::warning("Malayalam month lookup failed for {$ep['url']}: {$e->getMessage()}");
            }
        }

        return response()->json([
            'year'         => $year,
            'month'        => $month,
            'celebrations' => $celebrations,
        ]);
    }

    public function lookupDate(Request $request)
    {
        $request->validate(['date' => 'required|date_format:Y-m-d']);
        $dateStr = $request->input('date');
        $carbon = \Carbon\Carbon::parse($dateStr);
        $weekday = $carbon->format('l');

        $events = [];
        $sources = [];

        // 1) AlAdhan: Gregorian → Hijri conversion
        try {
            $hijriResp = Http::timeout(10)->get("https://api.aladhan.com/v1/gToH", [
                'date' => $carbon->format('d-m-Y'),
            ]);
            if ($hijriResp->successful()) {
                $hijriData = $hijriResp->json()['data']['hijri'] ?? null;
                if ($hijriData) {
                    $hMonth = (int) $hijriData['month']['number'];
                    $hDay = (int) $hijriData['day'];
                    $hYear = (int) $hijriData['year'];
                    $hMonthName = $hijriData['month']['en'];
                    $hijriDisplay = "{$hijriData['day']} {$hMonthName} {$hYear} AH";
                    $sources[] = 'aladhan';

                    // Check AlAdhan's own holiday list
                    $apiHolidays = $hijriData['holidays'] ?? [];
                    $apiAdjusted = $hijriData['adjustedHolidays'] ?? [];
                    foreach (array_merge($apiHolidays, $apiAdjusted) as $holiday) {
                        if (is_string($holiday) && trim($holiday) !== '') {
                            $events[] = [
                                'name'        => trim($holiday),
                                'calendar'    => 'hijri',
                                'description' => "{$hijriDisplay} — AlAdhan API holiday",
                                'source'      => 'aladhan_holidays',
                            ];
                        }
                    }

                    foreach ($this->getHijriEventMap() as $ev) {
                        if ($ev['month'] === $hMonth && $ev['day'] === $hDay) {
                            $events[] = [
                                'name'        => $ev['name'],
                                'calendar'    => 'hijri',
                                'description' => $hijriDisplay,
                                'source'      => 'hijri_event_map',
                            ];
                        }
                    }

                    // Check if it's the 1st of any month → new month notification
                    if ($hDay === 1 && empty(array_filter($events, fn($e) => ($e['source'] ?? '') === 'hijri_event_map' && str_contains($e['name'] ?? '', 'New Year')))) {
                        $events[] = [
                            'name'        => "1st {$hMonthName} — Beginning of new Islamic month",
                            'calendar'    => 'hijri',
                            'description' => $hijriDisplay,
                            'source'      => 'hijri_new_month',
                            'is_date'     => true,
                        ];
                    }

                    // Always include the Hijri date info
                    $events[] = [
                        'name'        => $hijriDisplay,
                        'calendar'    => 'hijri',
                        'description' => "{$weekday} — {$hijriDisplay}",
                        'is_date'     => true,
                        'source'      => 'hijri_date_info',
                    ];
                }
            }
        } catch (\Exception $e) {
            \Log::warning("Hijri date lookup failed: {$e->getMessage()}");
        }

        // 2) English Calendar events (fixed dates)
        foreach ($this->getEnglishEventMap() as $ev) {
            if ($ev['month'] === $carbon->month && $ev['day'] === $carbon->day) {
                $events[] = [
                    'name'        => $ev['name'],
                    'calendar'    => 'english',
                    'description' => $carbon->format('F j'),
                    'source'      => 'english_fixed',
                ];
            }
        }

        // 3) ml-panchangam: daily panchangam
        $mlEndpoints = [
            'https://ml-panchangam.api.divineapi.com/api/panchangam',
            'https://panchangam.api.divineapi.com/api/panchangam',
        ];
        foreach ($mlEndpoints as $mlUrl) {
            try {
                $mlResp = Http::timeout(8)->get($mlUrl, [
                    'date' => $carbon->format('Y-m-d'),
                ]);
                if ($mlResp->successful()) {
                    $mlData = $mlResp->json();

                    $kollavarsham = $mlData['kollavarsham'] ?? null;
                    $mlDate = $mlData['ml_date'] ?? null;
                    $mlMonth = $mlData['ml_month'] ?? null;
                    $mlDay = $mlData['ml_day_name'] ?? null;
                    $sources[] = 'ml_panchangam';

                    $vishesham = $mlData['vishesham'] ?? [];
                    if (is_array($vishesham)) {
                        foreach ($vishesham as $v) {
                            if (is_string($v) && trim($v) !== '') {
                                $events[] = [
                                    'name'        => trim($v),
                                    'calendar'    => 'malayalam',
                                    'description' => $mlDate ? "{$mlMonth} {$mlDate}, Kollavarsham {$kollavarsham}" : 'Malayalam event',
                                    'source'      => 'ml_vishesham',
                                ];
                            }
                        }
                    }

                    if ($mlDate) {
                        $events[] = [
                            'name'        => "{$mlMonth} {$mlDate}, Kollavarsham {$kollavarsham}",
                            'calendar'    => 'malayalam',
                            'description' => 'Malayalam date',
                            'is_date'     => true,
                            'source'      => 'ml_date_info',
                        ];
                    }
                    break; // Success on first working endpoint
                }
            } catch (\Exception $e) {
                \Log::warning("Malayalam date lookup failed for {$mlUrl}: {$e->getMessage()}");
            }
        }

        return response()->json([
            'date'    => $dateStr,
            'weekday' => $weekday,
            'sources' => $sources,
            'events'  => $events,
        ]);
    }

    public function activeFestivals()
    {
        $festivals = Festival::active()->get()->filter(function ($festival) {
            return $festival->isCurrentlyActive();
        })->values();

        $result = [];
        foreach ($festivals as $festival) {
            $setting = \App\Models\AdminSetting::first();
            $basePrice = $setting ? $setting->getUnlockPrice() : 49;
            $occurrence = $festival->occurrences()->where('year', now()->year)->first();
            $result[] = [
                'id' => $festival->id,
                'celebration_name' => $festival->celebration_name,
                'offer_discount' => (float) $festival->offer_discount,
                'offer_discount_type' => $festival->offer_discount_type,
                'discount_value' => $festival->getDiscountValue($basePrice),
                'discounted_price' => max(0, $basePrice - $festival->getDiscountValue($basePrice)),
                'ends_at' => $occurrence ? $occurrence->end_at->toIso8601String() : null,
            ];
        }

        return response()->json(['active_festivals' => $result]);
    }
}
