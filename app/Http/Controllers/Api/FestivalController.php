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

    public function lookupDate(Request $request)
    {
        $request->validate(['date' => 'required|date_format:Y-m-d']);
        $dateStr = $request->input('date');
        $carbon = \Carbon\Carbon::parse($dateStr);

        $events = [];

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
                    $hijriDisplay = "{$hijriData['day']} {$hijriData['month']['en']} {$hijriData['year']} AH";

                    $hijriEventMap = [
                        ['month' => 10, 'day' => 1,  'name' => 'Eid-ul-Fitr'],
                        ['month' => 10, 'day' => 2,  'name' => 'Eid-ul-Fitr (Day 2)'],
                        ['month' => 10, 'day' => 3,  'name' => 'Eid-ul-Fitr (Day 3)'],
                        ['month' => 12, 'day' => 10, 'name' => 'Eid-ul-Adha'],
                        ['month' => 12, 'day' => 11, 'name' => 'Eid-ul-Adha (Day 2)'],
                        ['month' => 12, 'day' => 12, 'name' => 'Eid-ul-Adha (Day 3)'],
                        ['month' => 12, 'day' => 13, 'name' => 'Eid-ul-Adha (Day 4)'],
                        ['month' => 9,  'day' => 1,  'name' => 'Ramadan Start'],
                        ['month' => 1,  'day' => 1,  'name' => 'Islamic New Year'],
                        ['month' => 7,  'day' => 27, 'name' => 'Isra and Miraj'],
                        ['month' => 3,  'day' => 12, 'name' => 'Mawlid (Prophet\'s Birthday)'],
                    ];

                    foreach ($hijriEventMap as $ev) {
                        if ($ev['month'] === $hMonth && $ev['day'] === $hDay) {
                            $events[] = [
                                'name'        => $ev['name'],
                                'calendar'    => 'hijri',
                                'description' => $hijriDisplay,
                            ];
                        }
                    }

                    // Always include the Hijri date info even if no specific event
                    if (empty(array_filter($events, fn($e) => $e['calendar'] === 'hijri' && empty($e['is_date'])))) {
                        $events[] = [
                            'name'        => $hijriDisplay,
                            'calendar'    => 'hijri',
                            'description' => 'Hijri date',
                            'is_date'     => true,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::warning("Hijri date lookup failed: {$e->getMessage()}");
        }

        // 2) ml-panchangam: daily panchangam
        try {
            $mlResp = Http::timeout(10)->get("https://ml-panchangam.api.divineapi.com/api/panchangam", [
                'date' => $carbon->format('Y-m-d'),
            ]);
            if ($mlResp->successful()) {
                $mlData = $mlResp->json();

                $kollavarsham = $mlData['kollavarsham'] ?? null;
                $mlDate = $mlData['ml_date'] ?? null;
                $mlMonth = $mlData['ml_month'] ?? null;

                $vishesham = $mlData['vishesham'] ?? [];
                if (is_array($vishesham)) {
                    foreach ($vishesham as $v) {
                        if (is_string($v) && trim($v) !== '') {
                            $events[] = [
                                'name'        => $v,
                                'calendar'    => 'malayalam',
                                'description' => $mlDate ? "{$mlMonth} {$mlDate}, Kollavarsham {$kollavarsham}" : 'Malayalam event',
                            ];
                        }
                    }
                }

                // Always include Malayalam date info
                if ($mlDate) {
                    $events[] = [
                        'name'        => "{$mlMonth} {$mlDate}, Kollavarsham {$kollavarsham}",
                        'calendar'    => 'malayalam',
                        'description' => 'Malayalam date',
                        'is_date'     => true,
                    ];
                }
            }
        } catch (\Exception $e) {
            \Log::warning("Malayalam date lookup failed: {$e->getMessage()}");
        }

        return response()->json([
            'date'   => $dateStr,
            'events' => $events,
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
