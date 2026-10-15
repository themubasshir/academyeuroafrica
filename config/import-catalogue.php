<?php

/*
 * Catalogue import manifest. Maps the client source archive
 * ("EAMN Academy+Training.rar") onto the academic schema.
 *
 *   source_root  absolute path to the extracted archive on the server
 *   programmes[] one course-programme
 *     courses[]  individual courses inside it
 *       modules[] explicit module list OR
 *       manual_file  DOCX parsed into modules at import
 *       materials[] source files attached to the course
 *
 * `audience: trainers` → hidden from students (trainer manuals, answer keys,
 * full implementation packages). `audience: students` → visible to enrollees.
 *
 * All rows are created as drafts. Nothing is published, priced or given a
 * PayPal button here — that is a separate, explicit step.
 */

return [
    'source_root' => env('IMPORT_SOURCE_ROOT', storage_path('app/import-source')),

    'programmes' => [

        // ── Digital Skills & Inclusion ────────────────────────────────────
        [
            'slug' => 'digital-skills-inclusion',
            'name' => 'Digital Skills & Inclusion',
            'description' => 'Digital literacy programmes — from a general digital-skills curriculum to a technology-inclusion course for older adults.',
            'sort_order' => 4,
            'courses' => [
                [
                    'slug' => 'digital-skills-training',
                    'title' => 'Digital Skills Training',
                    'description' => 'Core digital-skills curriculum. Source document also contains the landing-page, marketing and LMS structure for the course.',
                    'delivery_types' => ['live'],
                    'sort_order' => 1,
                    // No Module/Lesson structure in source — imported as an
                    // unpublished draft; curriculum broken down at review.
                    'materials' => [
                        ['file' => 'TRAINING-Courses-Academy/EAMN_Digital_Skills_Complete_Pack.docx',
                            'source_key' => 'implementation-pack', 'audience' => 'trainers',
                            'label' => 'Digital Skills — complete implementation pack'],
                    ],
                ],
                [
                    'slug' => 'elderly-digital-inclusion',
                    'title' => 'Elderly Digital Inclusion Training Programme',
                    'description' => 'Helping older adults (Germany & Nigeria) gain confidence with smartphones, apps and online services — connecting generations through technology.',
                    'delivery_types' => ['live'],
                    'sort_order' => 2,
                    'materials' => [
                        ['file' => 'TRAINING-Courses-Academy/Elderly Digital Inclusion Training Programme.docx',
                            'source_key' => 'programme-doc', 'audience' => 'trainers',
                            'label' => 'Elderly Digital Inclusion — programme document'],
                    ],
                ],
            ],
        ],

        // ── German Language School ────────────────────────────────────────
        [
            'slug' => 'german-language-school',
            'name' => 'EAMN German Language School',
            'description' => 'German courses for nurses, skilled workers, students and family reunification — A1 through B2+, conversation club and self-paced options.',
            'sort_order' => 5,
            'courses' => [
                [
                    'slug' => 'german-courses',
                    'title' => 'German Courses',
                    'description' => 'One-to-one, small-group and intensive German courses plus a Saturday conversation club. Source is a business/delivery plan — syllabus broken down at review.',
                    'delivery_types' => ['live'],
                    'sort_order' => 1,
                    'materials' => [
                        ['file' => 'TRAINING-Courses-Academy/GERMAN LANGUAGE SCHOOL/EAMN SPECIAL GERMAN SCHOOL.docx',
                            'source_key' => 'delivery-plan', 'audience' => 'trainers',
                            'label' => 'German School — delivery & pricing plan'],
                    ],
                ],
            ],
        ],

        // ── Intercultural Healthcare Academy ──────────────────────────────
        [
            'slug' => 'intercultural-healthcare-academy',
            'name' => 'Intercultural Healthcare Academy',
            'description' => 'Workplace-integration training for internationally educated nurses and healthcare professionals starting work in Germany.',
            'sort_order' => 1,
            'courses' => [
                [
                    'slug' => 'intercultural-success-in-german-healthcare',
                    'title' => 'Intercultural Success in German Healthcare',
                    'description' => 'A practical workplace-integration programme: understand the system, communicate safely, work across cultures, build a future in German healthcare.',
                    'delivery_types' => ['live'],
                    'sort_order' => 1,
                    'modules' => [
                        ['file' => 'EAMN COURSES COMPLETE FOLDER/EAMN_Flagship_Course_Module_1_Implementation_Package.docx', 'source_key' => 'm1'],
                        ['file' => 'EAMN COURSES COMPLETE FOLDER/EAMN_Flagship_Course_Module_2_Complete.docx', 'source_key' => 'm2'],
                        ['file' => 'EAMN COURSES COMPLETE FOLDER/EAMN_Flagship_Course_Module_3_UPDATED_Professional_Voice_Across_Cultures.docx', 'source_key' => 'm3'],
                        ['file' => 'EAMN COURSES COMPLETE FOLDER/EAMN_Flagship_Course_Module_4_Complete.docx', 'source_key' => 'm4'],
                        ['file' => 'EAMN COURSES COMPLETE FOLDER/EAMN_Flagship_Course_Module_5_Complete.docx', 'source_key' => 'm5'],
                        ['file' => 'EAMN COURSES COMPLETE FOLDER/EAMN_Flagship_Course_Module_6_Complete.docx', 'source_key' => 'm6'],
                        ['file' => 'EAMN COURSES COMPLETE FOLDER/EAMN_Flagship_Course_Module_7_Complete.docx', 'source_key' => 'm7'],
                        ['file' => 'EAMN COURSES COMPLETE FOLDER/EAMN_Flagship_Course_Module_8_Complete.docx', 'source_key' => 'm8'],
                        ['file' => 'EAMN COURSES COMPLETE FOLDER/EAMN_Flagship_Course_Module_9_Complete.docx', 'source_key' => 'm9'],
                        ['file' => 'EAMN COURSES COMPLETE FOLDER/EAMN_Flagship_Course_Module_10_Complete.docx', 'source_key' => 'm10'],
                    ],
                    'materials' => [
                        ['file' => 'EAMN COURSES COMPLETE FOLDER/EAMN_The_First_30_Days_in_German_Healthcare.pdf',
                            'source_key' => 'first-30-days-pdf', 'audience' => 'students',
                            'label' => 'The First 30 Days in German Healthcare — starter guide'],
                        ['file' => 'EAMN COURSES COMPLETE FOLDER/EAMN_The_First_30_Days_in_German_Healthcare.docx',
                            'source_key' => 'first-30-days-docx', 'audience' => 'trainers',
                            'label' => 'The First 30 Days — editable source'],
                    ],
                ],
            ],
        ],

        // ── Personal, Family & Travel Security ────────────────────────────
        [
            'slug' => 'security-academy',
            'name' => 'No Easy Target Security Academy',
            'description' => 'Practical, prevention-first personal security training for diaspora travellers, families and community organisations.',
            'sort_order' => 2,
            'courses' => [
                [
                    'slug' => 'no-easy-target',
                    'title' => 'No Easy Target — Personal, Family and Travel Security',
                    'description' => 'Replace vague fear with practical routines for personal, family, travel, home and digital safety. 8 modules · 24 lessons.',
                    'delivery_types' => ['live'],
                    'sort_order' => 1,
                    'manual_file' => 'TRAINING-Courses-Academy/NO EASY TARGET -SECURITY COURSES/EAMN_No_Easy_Target_Full_Trainer_Manual(1).docx',
                    'materials' => [
                        ['file' => 'TRAINING-Courses-Academy/NO EASY TARGET -SECURITY COURSES/EAMN_No_Easy_Target_Participant_Workbook.docx',
                            'source_key' => 'participant-workbook', 'audience' => 'students',
                            'label' => 'Participant workbook'],
                        ['file' => 'TRAINING-Courses-Academy/NO EASY TARGET -SECURITY COURSES/EAMN_No_Easy_Target_Trainer_Presentation.pptx',
                            'source_key' => 'trainer-slides', 'audience' => 'trainers',
                            'label' => 'Trainer presentation (slides)'],
                    ],
                ],
            ],
        ],

        // ── Financial Security ────────────────────────────────────────────
        [
            'slug' => 'wealth-academy',
            'name' => 'Business & Wealth Academy',
            'description' => 'Financial literacy, budgeting and long-term wealth-building courses for migrants and diaspora communities.',
            'sort_order' => 3,
            'courses' => [
                [
                    'slug' => 'build-financial-security-germany',
                    'title' => 'Build Financial Security in Germany',
                    'description' => 'Budgeting, emergency funds, pensions, saving & investing and a twelve-month wealth plan for life in Germany.',
                    'delivery_types' => ['live'],
                    'sort_order' => 1,
                    'manual_file' => 'TRAINING-Courses-Academy/WEALTH ACADEMY/EAMN_Business_Wealth_Academy_Full_Trainer_Manual(1).docx',
                    'materials' => [
                        ['file' => 'TRAINING-Courses-Academy/WEALTH ACADEMY/EAMN_Business_Wealth_Academy_Participant_Workbook.docx',
                            'source_key' => 'participant-workbook', 'audience' => 'students',
                            'label' => 'Participant workbook'],
                        ['file' => 'TRAINING-Courses-Academy/WEALTH ACADEMY/EAMN_Business_Wealth_Academy_Trainer_Presentation.pptx',
                            'source_key' => 'trainer-slides', 'audience' => 'trainers',
                            'label' => 'Trainer presentation (slides)'],
                    ],
                ],
                [
                    'slug' => 'start-small-earn-big-africa',
                    'title' => 'Start Small, Earn Big in Africa',
                    'description' => 'Entrepreneurship and small-business fundamentals for building income streams across African markets.',
                    'delivery_types' => ['live'],
                    'sort_order' => 2,
                    // The participant workbook carries the structured
                    // Module/Lesson content; the separate "trainer manual" in
                    // this folder is a duplicate of the Wealth Academy manual.
                    'manual_file' => 'TRAINING-Courses-Academy/START SMALL EARN BIG ACADEMY/EAMN_Start_Small_Earn_Big_in_Africa_Participant_Workbook.docx',
                    'materials' => [
                        ['file' => 'TRAINING-Courses-Academy/START SMALL EARN BIG ACADEMY/EAMN_Start_Small_Earn_Big_in_Africa_Trainer_Presentation.pptx',
                            'source_key' => 'trainer-slides', 'audience' => 'trainers',
                            'label' => 'Trainer presentation (slides)'],
                    ],
                ],
            ],
        ],
    ],
];
