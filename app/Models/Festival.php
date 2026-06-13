<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class Festival extends Model
{
    protected $fillable = [
        'celebration_name',
        'offer_discount',
        'offer_discount_type',
        'calendar_type',
        'hijri_event',
        'ml_event',
        'fixed_month',
        'fixed_day',
        'start_offset_days',
        'end_offset_days',
        'reminder_days_before',
        'is_active',
        'target_gender',
    ];

    protected $casts = [
        'offer_discount' => 'decimal:2',
        'fixed_month' => 'integer',
        'fixed_day' => 'integer',
        'start_offset_days' => 'integer',
        'end_offset_days' => 'integer',
        'reminder_days_before' => 'integer',
        'is_active' => 'boolean',
    ];

    public function occurrences()
    {
        return $this->hasMany(FestivalOccurrence::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function resolveDatesForYear(int $year): array
    {
        $dates = match ($this->calendar_type) {
            'gregorian_fixed' => $this->resolveGregorianFixed($year),
            'hijri'           => $this->resolveHijri($year),
            'malayalam'       => $this->resolveMalayalam($year),
            default           => null,
        };

        if (!$dates) {
            return ['start_at' => null, 'end_at' => null, 'resolved_from' => null];
        }

        return [
            'start_at'      => $dates['event_date']->copy()->subDays($this->start_offset_days)->startOfDay(),
            'end_at'        => $dates['event_date']->copy()->addDays($this->end_offset_days)->endOfDay(),
            'resolved_from' => $dates['source'] ?? $this->calendar_type,
        ];
    }

    protected function resolveGregorianFixed(int $year): ?array
    {
        if (!$this->fixed_month || !$this->fixed_day) {
            return null;
        }
        return [
            'event_date' => \Carbon\Carbon::create($year, $this->fixed_month, $this->fixed_day),
            'source'     => 'gregorian_fixed',
        ];
    }

    protected function resolveHijri(int $year): ?array
    {
        $hijriEventMap = [
            'eid_ul_fitr'  => ['month' => 10, 'day' => 1],
            'eid_ul_adha'  => ['month' => 12, 'day' => 10],
            'ramadan_start' => ['month' => 9, 'day' => 1],
        ];

        $hijri = $hijriEventMap[$this->hijri_event] ?? null;
        if (!$hijri) {
            return null;
        }

        try {
            $response = Http::timeout(10)->get("https://api.aladhan.com/v1/hToG", [
                'month'  => $hijri['month'],
                'year'   => $this->getHijriYearForGregorian($year),
                'day'    => $hijri['day'],
                'adjust' => 0,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $date = $data['data']['gregorian']['date'] ?? null;
                if ($date) {
                    return [
                        'event_date' => \Carbon\Carbon::parse($date),
                        'source'     => 'aladhan_hijri',
                    ];
                }
            }
        } catch (\Exception $e) {
            \Log::warning("Hijri date resolution failed for {$this->hijri_event} year {$year}: {$e->getMessage()}");
        }

        return null;
    }

    protected function resolveMalayalam(int $year): ?array
    {
        $mlEventMap = [
            'onam'         => 'ഓണം',
            'vishu'        => 'വിഷു',
            'thiruvathira' => 'തിരുവാതിര',
            'deepavali'    => 'ദീപാവലി',
            'shivarathri'  => 'മഹാശിവരാത്രി',
            'navaratri'    => 'നവരാത്രി',
        ];

        $mlName = $mlEventMap[$this->ml_event] ?? null;
        if (!$mlName) {
            return null;
        }

        try {
            // Fetch panchangam for a reference date to get the API base
            // First try the upcoming-events endpoint
            $response = Http::timeout(10)->get("https://ml-panchangam.api.divineapi.com/api/tools/upcoming-events", [
                'count' => 50,
            ]);

            if ($response->successful()) {
                $events = $response->json();
                if (is_array($events)) {
                    foreach ($events as $event) {
                        if (isset($event['event']) && $event['event'] === $mlName) {
                            $eventDate = \Carbon\Carbon::parse($event['date']);
                            if ($eventDate->year === $year) {
                                return [
                                    'event_date' => $eventDate,
                                    'source'     => 'ml_panchangam_upcoming',
                                ];
                            }
                        }
                    }
                }
            }

            // Fallback: Fetch a full month around Chingam (Aug-Sep) for Onam
            $monthMap = [
                'ഓണം' => ['month' => 8, 'max_days' => 45],
                'വിഷു' => ['month' => 4, 'max_days' => 15],
            ];

            $fallback = $monthMap[$mlName] ?? null;
            if ($fallback) {
                $start = \Carbon\Carbon::create($year, $fallback['month'], 1);
                $end = (clone $start)->addDays($fallback['max_days']);

                while ($start->lte($end)) {
                    $dayResp = Http::timeout(5)->get("https://ml-panchangam.api.divineapi.com/api/panchangam", [
                        'date' => $start->format('Y-m-d'),
                    ]);
                    if ($dayResp->successful()) {
                        $dayData = $dayResp->json();
                        $vishesham = $dayData['vishesham'] ?? [];
                        if (is_array($vishesham) && in_array($mlName, $vishesham)) {
                            return [
                                'event_date' => clone $start,
                                'source'     => 'ml_panchangam_daily',
                            ];
                        }
                    }
                    $start->addDay();
                }
            }
        } catch (\Exception $e) {
            \Log::warning("Malayalam date resolution failed for {$this->ml_event} year {$year}: {$e->getMessage()}");
        }

        return null;
    }

    protected function getHijriYearForGregorian(int $gregorianYear): int
    {
        // Approximate: Hijri year ≈ Gregorian year - 622 + (Gregorian year - 622) / 32
        return (int) floor(($gregorianYear - 622) * 1.0307);
    }

    public function ensureOccurrenceForYear(int $year): ?FestivalOccurrence
    {
        $resolved = $this->resolveDatesForYear($year);
        if (!$resolved['start_at'] || !$resolved['end_at']) {
            $existing = $this->occurrences()->where('year', $year)->first();
            if ($existing) {
                $existing->delete();
            }
            return null;
        }

        $existing = $this->occurrences()->where('year', $year)->first();
        if ($existing) {
            $existing->update([
                'start_at'      => $resolved['start_at'],
                'end_at'        => $resolved['end_at'],
                'resolved_from' => $resolved['resolved_from'],
            ]);
            return $existing->fresh();
        }

        return $this->occurrences()->create([
            'year'          => $year,
            'start_at'      => $resolved['start_at'],
            'end_at'        => $resolved['end_at'],
            'resolved_from' => $resolved['resolved_from'],
        ]);
    }

    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Try cached occurrence first
        $occurrence = $this->occurrences()->where('year', now()->year)->first();
        if ($occurrence) {
            return $occurrence->start_at <= now() && $occurrence->end_at >= now();
        }

        // Fall back to on-the-fly resolution
        $resolved = $this->resolveDatesForYear(now()->year);
        if (!$resolved['start_at'] || !$resolved['end_at']) {
            return false;
        }

        return $resolved['start_at'] <= now() && $resolved['end_at'] >= now();
    }

    public function getDiscountValue(float $basePrice): float
    {
        if (!$this->offer_discount || $this->offer_discount <= 0) {
            return 0;
        }
        if ($this->offer_discount_type === 'percentage') {
            return round($basePrice * ($this->offer_discount / 100), 2);
        }
        return $this->offer_discount;
    }

    public function matchesGender(?string $gender): bool
    {
        if ($this->target_gender === null || $this->target_gender === '') {
            return true;
        }
        if ($gender === null) {
            return false;
        }
        return strtolower($this->target_gender) === strtolower($gender);
    }

    public static function getBestActiveDiscount(float $basePrice, ?string $gender = null): array
    {
        $festivals = static::active()->get();
        $bestDiscount = 0;
        $bestFestival = null;

        foreach ($festivals as $festival) {
            if (!$festival->isCurrentlyActive()) {
                continue;
            }
            if (!$festival->matchesGender($gender)) {
                continue;
            }
            $discount = $festival->getDiscountValue($basePrice);
            if ($discount > $bestDiscount) {
                $bestDiscount = $discount;
                $bestFestival = $festival;
            }
        }

        return [
            'discount'     => $bestDiscount,
            'festival'     => $bestFestival,
            'discounted_price' => max(0, round($basePrice - $bestDiscount, 2)),
        ];
    }
}
