<?php

/*
 * Commercial package (CourseVariant) definitions for the imported catalogue.
 *
 * Every price/inclusion/duration below is copied verbatim from a client source
 * document — `price_source` names the file + section. Where the source gives a
 * range ("€99–139") or no price at all, the row is marked `pending` and left
 * unpublished; nothing here ever reaches checkout until an admin approves it.
 *
 * Seeded by `catalogue:variants` (idempotent on source_key). Never overwrites
 * an admin-edited variant.
 */

return [

    // ── Intercultural Success in German Healthcare ─────────────────────────
    // Source: "MASTER PLAN - COURSES - Intercultural Healthcare Academy.docx"
    //         §3 Pricing strategy — "a ladder rather than one price".
    'intercultural-success-in-german-healthcare' => [
        [
            'source_key' => 'essential',
            'name' => 'Essential',
            'delivery_type' => 'prerecorded',
            'price_cents' => 9900,                       // €99 — documented
            'duration' => 'Self-study · ~25–30 h',
            'inclusions' => [
                'Complete self-study programme',
                'Workbook, exercises and case studies',
                'Completion certificate',
            ],
            'price_source' => 'MASTER PLAN — Intercultural Healthcare Academy · §3 "Essential — €99"',
        ],
        [
            'source_key' => 'professional',
            'name' => 'Professional',
            'delivery_type' => 'hybrid',
            'price_cents' => 24900,                      // €249 — documented
            'duration' => 'Self-study + live monthly sessions',
            'inclusions' => [
                'Everything in Essential',
                'Live monthly Q&A',
                'Case-discussion sessions',
                'Access to recordings',
            ],
            'price_source' => 'MASTER PLAN — Intercultural Healthcare Academy · §3 "Professional — €249"',
        ],
        [
            'source_key' => 'premium',
            'name' => 'Premium',
            'delivery_type' => 'live',
            'price_cents' => 59900,                      // €599 — documented
            'duration' => '6–8-week cohort · live teaching',
            'inclusions' => [
                'Live teaching',
                'Role-play and small-group case work',
                'One individual consultation',
                'Payment option 3 × €209 (per source)',
            ],
            'price_source' => 'MASTER PLAN — Intercultural Healthcare Academy · §3 "Premium — €599"',
        ],
        [
            'source_key' => 'institutional',
            'name' => 'Institutional',
            'delivery_type' => 'private',
            'price_cents' => null,                        // range only — pending
            'duration' => 'Customised cohort / onboarding',
            'inclusions' => [
                'Quoted per group size and customisation',
            ],
            'price_source' => 'MASTER PLAN · §3 "Institutional — €1,500–€5,000+ (range, quote on application)"',
            'pending' => 'price is a range; needs a fixed figure or "POA" handling',
        ],
    ],

    // ── No Easy Target ─────────────────────────────────────────────────────
    // Manual documents duration/format but §"Commercial Offer Ladder" is an
    // empty heading — NO prices anywhere in source. All variants pending.
    'no-easy-target' => [
        [
            'source_key' => 'standard',
            'name' => 'Standard Course',
            'delivery_type' => 'live',
            'price_cents' => null,                        // no price in source
            'duration' => '36 taught hours + 12 h guided planning',
            'inclusions' => [
                'Eight modules · 24 lessons',
                'Defensive scenarios, checklists, tabletop exercises',
                'Final personal security plan',
                'EAMN certificate of completion',
            ],
            'price_source' => 'No Easy Target Trainer Manual · "Duration 36 taught hours" — NO price documented',
            'pending' => 'no price in source; admin must set',
        ],
    ],

    // ── Build Financial Security in Germany ────────────────────────────────
    'build-financial-security-germany' => [
        [
            'source_key' => 'standard',
            'name' => 'Standard Course',
            'delivery_type' => 'live',
            'price_cents' => null,
            'duration' => '8 modules · 24 lessons',
            'inclusions' => [
                'Budgeting, emergency funds, pensions',
                'Saving & investing foundations',
                'Twelve-month wealth plan',
                'EAMN certificate of completion',
            ],
            'price_source' => 'Business & Wealth Academy Trainer Manual · "Commercial Offer Ladder" heading empty — NO price documented',
            'pending' => 'no price in source; admin must set',
        ],
    ],

    // ── Start Small, Earn Big in Africa ────────────────────────────────────
    'start-small-earn-big-africa' => [
        [
            'source_key' => 'standard',
            'name' => 'Standard Course',
            'delivery_type' => 'live',
            'price_cents' => null,
            'duration' => '8 modules · 32 lessons',
            'inclusions' => [
                'Small-business and microenterprise fundamentals',
                'Diaspora business-management module',
                'Participant workbook',
            ],
            'price_source' => 'Start Small Earn Big workbook — NO price documented',
            'pending' => 'no price in source; admin must set',
        ],
    ],

    // ── Digital Skills Training (Virtual Assistant track) ──────────────────
    // Source: EAMN_Digital_Skills_Complete_Pack.docx · "Pricing (Optimised…)"
    'digital-skills-training' => [
        [
            'source_key' => 'early-bird',
            'name' => 'Early Bird',
            'delivery_type' => 'live',
            'price_cents' => 4900,                        // €49 — documented
            'duration' => '6-week virtual-assistant & digital-skills training',
            'inclusions' => ['Limited launch slots'],
            'price_source' => 'Digital Skills Complete Pack · "Early Bird Offer €49"',
        ],
        [
            'source_key' => 'standard',
            'name' => 'Standard',
            'delivery_type' => 'live',
            'price_cents' => null,                        // €99–139 range — pending
            'duration' => '6-week virtual-assistant & digital-skills training',
            'inclusions' => ['Full course access', 'Certificate'],
            'price_source' => 'Digital Skills Complete Pack · "Standard Price €99–€139" (range)',
            'pending' => 'price is a range €99–139; admin must fix a figure',
        ],
        [
            'source_key' => 'vip',
            'name' => 'VIP Package',
            'delivery_type' => 'live',
            'price_cents' => 22900,                       // €229 — documented
            'duration' => '6-week virtual-assistant & digital-skills training',
            'inclusions' => ['All Standard inclusions', 'VIP extras per source'],
            'price_source' => 'Digital Skills Complete Pack · "VIP-Package €229"',
        ],
    ],

    // ── EAMN German Language School ────────────────────────────────────────
    // Source: EAMN SPECIAL GERMAN SCHOOL.docx — full price ladder documented.
    'german-courses' => [
        ['source_key' => 'a1-complete', 'name' => 'German A1 Complete',
            'delivery_type' => 'prerecorded', 'price_cents' => 7900,
            'inclusions' => ['Self-paced A1 video course'],
            'price_source' => 'EAMN Special German School · "German A1 Complete €79"'],
        ['source_key' => 'a2', 'name' => 'German A2',
            'delivery_type' => 'prerecorded', 'price_cents' => 9900,
            'inclusions' => ['Self-paced A2 video course'],
            'price_source' => 'EAMN Special German School · "German A2 €99"'],
        ['source_key' => 'german-for-nurses', 'name' => 'German for Nurses',
            'delivery_type' => 'prerecorded', 'price_cents' => 14900,
            'inclusions' => ['Professional German for nursing staff'],
            'price_source' => 'EAMN Special German School · "German for Nurses €149"'],
        ['source_key' => 'living-in-germany', 'name' => 'German for Living in Germany',
            'delivery_type' => 'prerecorded', 'price_cents' => 9900,
            'inclusions' => ['Everyday / integration German'],
            'price_source' => 'EAMN Special German School · "German for Living in Germany €99"'],
        ['source_key' => 'medical-german', 'name' => 'Medical German',
            'delivery_type' => 'prerecorded', 'price_cents' => 19900,
            'inclusions' => ['Clinical / medical-professional German'],
            'price_source' => 'EAMN Special German School · "Medical German €199"'],
        ['source_key' => 'intensive', 'name' => 'Intensive Course (8-week)',
            'delivery_type' => 'live', 'price_cents' => null,
            'duration' => '90 min · twice weekly · 8 weeks',
            'inclusions' => ['Small-group intensive'],
            'price_source' => 'EAMN Special German School · "Intensive €299–399 per participant" (range)',
            'pending' => 'price is a range €299–399; admin must fix'],
        ['source_key' => 'conversation-club', 'name' => 'Conversation Club',
            'delivery_type' => 'live', 'price_cents' => null,
            'duration' => '1 h · every Saturday',
            'inclusions' => ['Unlimited speaking practice'],
            'price_source' => 'EAMN Special German School · "Conversation Club €10–15" (range)',
            'pending' => 'price is a range €10–15; admin must fix'],
    ],
];
