<?php

namespace Database\Seeders;

use App\Models\PortfolioFeature;
use App\Models\PortfolioProject;
use App\Models\PortfolioSkill;
use App\Models\UpworkAccount;
use Illuminate\Database\Seeder;

class HmsTechPortfolioSeeder extends Seeder
{
    public function run(): void
    {
        $accountId = UpworkAccount::query()->value('id');

        $projects = [
            [
                'external_id' => 'hmstech-2',
                'title' => 'Colala Mall – Multi Vendor Ecommerce Platform',
                'website_url' => 'https://hmstech.org/portfolio/2',
                'industry' => 'Ecommerce',
                'project_type' => 'Multi-vendor Marketplace',
                'description' => "Colala Mall is a modern multi-vendor ecommerce platform designed to provide a seamless online shopping experience. It allows multiple sellers to list products, manage inventory, and process orders efficiently through a user-friendly interface.\n\nThe platform also includes integrated social media features, enabling users to interact, share products, and engage with sellers directly. This combination enhances user experience and creates a more connected and interactive marketplace environment.",
                'raw_notes' => "What we built: full-featured ecommerce with multi-vendor support, product management, order processing, secure authentication, social media features for interaction and content sharing.\nWhy: centralized marketplace for shopping + social interaction.\nStack: Laravel, React.js, MySQL, REST API, Bootstrap, JavaScript",
                'skills' => ['Laravel', 'React.js', 'MySQL', 'REST API', 'Bootstrap', 'JavaScript'],
                'features' => [
                    'feature' => ['Multi-vendor marketplace', 'Product & inventory management', 'Order processing', 'Secure authentication', 'Social media integration', 'Seller-buyer engagement'],
                    'capability' => ['Ecommerce', 'Multi-vendor', 'Social commerce', 'REST APIs'],
                    'result' => ['Interactive marketplace combining shopping and social features'],
                ],
                'keywords' => ['ecommerce', 'marketplace', 'multi-vendor', 'social shopping', 'Laravel', 'React'],
            ],
            [
                'external_id' => 'hmstech-5',
                'title' => 'GymPaddy – Social Fitness Mobile App',
                'website_url' => 'https://hmstech.org/portfolio/5',
                'industry' => 'Fitness / Health',
                'project_type' => 'Social Mobile App',
                'description' => "GymPaddy is an innovative social fitness platform designed to bring people together through shared fitness goals and activities. It allows users to connect, participate in live sessions, and engage with a community-driven ecosystem.\n\nThe platform integrates social networking, live streaming, and creator monetization features to make fitness more interactive and rewarding.",
                'raw_notes' => "Built: social fitness ecosystem with user profiles, live streaming, community interaction, digital marketplace, trainer/creator monetization.\nStack: React Native, Laravel, MySQL, REST API, Firebase, WebSockets",
                'skills' => ['React Native', 'Laravel', 'MySQL', 'REST API', 'Firebase', 'WebSockets'],
                'features' => [
                    'feature' => ['User profiles', 'Live streaming', 'Community interaction', 'Creator monetization', 'Digital marketplace'],
                    'capability' => ['Mobile apps', 'Realtime', 'Social networking', 'Live streaming'],
                    'challenge' => ['Keeping users consistent with fitness through community motivation'],
                    'result' => ['Community-driven fitness platform with monetization for trainers'],
                ],
                'keywords' => ['fitness', 'React Native', 'live streaming', 'social app', 'Firebase'],
            ],
            [
                'external_id' => 'hmstech-1',
                'title' => 'Troosolar – Smart Solar Energy Platform',
                'website_url' => 'https://hmstech.org/portfolio/1',
                'industry' => 'Energy / Cleantech',
                'project_type' => 'IoT / Energy Management Platform',
                'description' => "TrooSolar is a smart solar energy platform developed to simplify the management of solar panel systems. It provides users with an intuitive interface to monitor energy production, track performance, and manage installations with ease and accuracy.\n\nThe system improves energy efficiency and reduces operational complexity with real-time insights and smart analytics.",
                'raw_notes' => "Built: solar management platform for panel performance monitoring, energy generation tracking, system data management.\nStack: React, React Native, Laravel, REST API, MySQL",
                'skills' => ['React', 'React Native', 'Laravel', 'REST API', 'MySQL'],
                'features' => [
                    'feature' => ['Solar panel monitoring', 'Energy production tracking', 'Performance analytics', 'Installation management', 'Mobile + web dashboards'],
                    'capability' => ['Energy platforms', 'Dashboards', 'Realtime monitoring', 'Cross-platform apps'],
                    'result' => ['Simplified solar system management with better energy utilization'],
                ],
                'keywords' => ['solar', 'energy', 'IoT', 'dashboard', 'Laravel', 'React Native'],
            ],
            [
                'external_id' => 'hmstech-6',
                'title' => 'Tercescrow – Secure Fintech Platform Crypto',
                'website_url' => 'https://hmstech.org/portfolio/6',
                'industry' => 'Fintech / Crypto',
                'project_type' => 'Fintech Platform',
                'description' => "Tercescrow is a modern fintech platform for secure digital transactions. Users can trade gift cards, manage crypto assets, and perform financial operations through a user-friendly interface focused on speed, security, and reliability.",
                'raw_notes' => "Built: fintech ecosystem with mobile apps + web — gift card trading, crypto management, secure payments, realtime transaction tracking.\nStack: React Native, Laravel, MySQL, REST API, Firebase, Blockchain APIs",
                'skills' => ['React Native', 'Laravel', 'MySQL', 'REST API', 'Firebase', 'Blockchain APIs'],
                'features' => [
                    'feature' => ['Gift card trading', 'Crypto asset management', 'Secure payments', 'Realtime transaction tracking', 'Mobile + web apps'],
                    'capability' => ['Fintech', 'Crypto', 'Payments', 'Mobile wallets'],
                    'result' => ['Unified secure platform for gift cards and crypto transactions'],
                ],
                'keywords' => ['fintech', 'crypto', 'gift cards', 'payments', 'React Native', 'Laravel'],
            ],
            [
                'external_id' => 'hmstech-7',
                'title' => 'Earlybaze Crypto Exchange Platform',
                'website_url' => 'https://hmstech.org/portfolio/7',
                'industry' => 'Fintech / Crypto',
                'project_type' => 'Crypto Exchange',
                'description' => "Earlybaze is a crypto exchange and digital asset management platform designed to simplify cryptocurrency trading for users in Nigeria and beyond. It provides a secure environment for buying, selling, and swapping cryptocurrencies with a beginner-friendly UI, realtime updates, wallet management, and strong mobile experience.",
                'raw_notes' => "Built: responsive crypto exchange with landing page, mobile-first design, trading, wallet integration, transaction tracking, secure auth, onboarding.\nStack: Laravel, React Native, MySQL, REST API",
                'skills' => ['Laravel', 'React Native', 'MySQL', 'REST API'],
                'features' => [
                    'feature' => ['Buy/sell/swap crypto', 'Wallet management', 'Realtime transaction updates', 'Secure authentication', 'Landing page + mobile apps'],
                    'capability' => ['Crypto exchange', 'Wallets', 'Fintech UX', 'Mobile trading'],
                    'result' => ['Beginner-friendly crypto trading platform for Nigeria market'],
                ],
                'keywords' => ['crypto exchange', 'wallet', 'trading', 'Laravel', 'React Native', 'Nigeria'],
            ],
            [
                'external_id' => 'hmstech-4',
                'title' => 'Ndozi Naturals – Organic Ecommerce Store',
                'website_url' => 'https://hmstech.org/portfolio/4',
                'industry' => 'Ecommerce / Organic Products',
                'project_type' => 'Ecommerce Store',
                'description' => "Ndozi Naturals is a modern ecommerce platform for selling organic and plant-based products. Users can browse categories, explore products, and make secure purchases with a focus on sustainability and product authenticity.",
                'raw_notes' => "Built: ecommerce with product listings, categories, filtering, secure checkout, responsive design.\nStack: WordPress, WooCommerce, PHP, MySQL, JavaScript, Bootstrap",
                'skills' => ['WordPress', 'WooCommerce', 'PHP', 'MySQL', 'JavaScript', 'Bootstrap'],
                'features' => [
                    'feature' => ['Product catalog', 'Category management', 'Filtering', 'Secure checkout', 'Responsive design'],
                    'capability' => ['WooCommerce', 'WordPress ecommerce', 'Organic retail'],
                    'result' => ['Accessible online store for organic plant-based products'],
                ],
                'keywords' => ['WordPress', 'WooCommerce', 'organic', 'ecommerce', 'checkout'],
            ],
            [
                'external_id' => 'hmstech-9',
                'title' => 'Above Lifestyle Lekki',
                'website_url' => 'https://hmstech.org/portfolio/9',
                'industry' => 'Hospitality / Nightlife',
                'project_type' => 'Business Website',
                'description' => "Above Lifestyle Lekki is a luxury lifestyle destination in Lagos combining fine dining, cocktails, nightlife, and entertainment. The website strengthens brand presence, simplifies reservations, and showcases menus, events, and private event options.",
                'raw_notes' => "Built: luxury hospitality website, restaurant & bar menus, table reservations, events showcase, gallery, contact forms, responsive design.\nStack: WordPress, HTML5, CSS3, JavaScript",
                'skills' => ['WordPress', 'HTML5', 'CSS3', 'JavaScript', 'Responsive Design'],
                'features' => [
                    'feature' => ['Restaurant & bar menus', 'Table reservation system', 'Event & activity showcase', 'Gallery', 'Contact forms'],
                    'capability' => ['Hospitality websites', 'Reservations', 'WordPress business sites'],
                    'result' => ['Strong digital presence for luxury Lagos hospitality venue'],
                ],
                'keywords' => ['hospitality', 'WordPress', 'reservations', 'restaurant', 'Lagos'],
            ],
            [
                'external_id' => 'hmstech-3',
                'title' => 'SkillVerse – Online Learning Platform',
                'website_url' => 'https://hmstech.org/portfolio/3',
                'industry' => 'EdTech',
                'project_type' => 'Learning Management Platform',
                'description' => "SkillVerse is a comprehensive online learning platform helping students develop practical skills through courses, internship opportunities, job preparation resources, and expert guidance.",
                'raw_notes' => "Built: e-learning with course management, enrollment, progress tracking, content delivery, internship listings, career support.\nStack: Laravel, MySQL, REST API, Bootstrap, JavaScript",
                'skills' => ['Laravel', 'MySQL', 'REST API', 'Bootstrap', 'JavaScript'],
                'features' => [
                    'feature' => ['Course management', 'Student enrollment', 'Progress tracking', 'Internship listings', 'Career support tools'],
                    'capability' => ['LMS', 'EdTech', 'Career platforms'],
                    'result' => ['Bridge between education and industry job readiness'],
                ],
                'keywords' => ['LMS', 'e-learning', 'Laravel', 'internships', 'courses'],
            ],
            [
                'external_id' => 'hmstech-10',
                'title' => 'West Sydney Lions Basketball Academy',
                'website_url' => 'https://hmstech.org/portfolio/10',
                'industry' => 'Sports / Education',
                'project_type' => 'Sports Academy Website',
                'description' => "Website for West Sydney Lions Basketball Academy showcasing training programs, coaching staff, registration, schedules, and academy information for players of all skill levels.",
                'raw_notes' => "Built: sports academy site, training program pages, online registration, coach profiles, events/news, responsive design, social integration.\nStack: WordPress, HTML5, CSS3, JavaScript",
                'skills' => ['WordPress', 'HTML5', 'CSS3', 'JavaScript'],
                'features' => [
                    'feature' => ['Training program pages', 'Online registration', 'Coach profiles', 'Events & news', 'Social media integration'],
                    'capability' => ['Sports websites', 'Registration systems', 'WordPress'],
                ],
                'keywords' => ['sports', 'basketball', 'WordPress', 'academy', 'registration'],
            ],
            [
                'external_id' => 'hmstech-8',
                'title' => 'Arctic Gynae Centre Website',
                'website_url' => 'https://hmstech.org/portfolio/8',
                'industry' => 'Healthcare',
                'project_type' => 'Healthcare Website',
                'description' => "Specialist obstetrics and gynaecology website for Arctic Gynae Centre (Lagos). Showcases services, doctor profiles, consultation booking, and patient education content for women's health.",
                'raw_notes' => "Built: responsive healthcare website, service pages, doctor profiles, appointment booking, contact/inquiry, medical blog, SEO.\nStack: WordPress, HTML5, CSS3, JavaScript, Contact Forms, SEO",
                'skills' => ['WordPress', 'HTML5', 'CSS3', 'JavaScript', 'SEO'],
                'features' => [
                    'feature' => ['Service management pages', 'Doctor profiles', 'Appointment booking forms', 'Medical blog', 'Mobile-friendly UI'],
                    'capability' => ['Healthcare websites', 'Appointment booking', 'Medical SEO'],
                    'result' => ['Improved online visibility and easier appointment booking for patients'],
                ],
                'keywords' => ['healthcare', 'WordPress', 'gynaecology', 'appointments', 'Lagos'],
            ],
            [
                'external_id' => 'hmstech-11',
                'title' => 'MyDoctor+ Patient Portal',
                'website_url' => 'https://hmstech.org/portfolio/11',
                'industry' => 'Healthcare / HealthTech',
                'project_type' => 'Patient Portal',
                'description' => "MyDoctor+ connects patients with medical professionals through a digital portal for appointments, communication, service requests, and healthcare journey management across devices.",
                'raw_notes' => "Built: patient portal, healthcare management, appointment management, secure auth, patient dashboard, communication features, responsive design.\nStack: Laravel/PHP, MySQL, HTML5, CSS3, JavaScript, Bootstrap",
                'skills' => ['Laravel', 'PHP', 'MySQL', 'HTML5', 'CSS3', 'JavaScript', 'Bootstrap'],
                'features' => [
                    'feature' => ['Patient portal', 'Appointment management', 'Secure authentication', 'Patient dashboard', 'Provider communication'],
                    'capability' => ['HealthTech', 'Patient portals', 'Healthcare SaaS'],
                    'result' => ['Centralized digital experience for patient-provider interactions'],
                ],
                'keywords' => ['patient portal', 'healthcare', 'Laravel', 'appointments', 'dashboard'],
            ],
            [
                'external_id' => 'hmstech-12',
                'title' => 'Career Institute Pakistan',
                'website_url' => 'https://hmstech.org/portfolio/12',
                'industry' => 'Education',
                'project_type' => 'Educational Institution Website',
                'description' => "Career Institute Pakistan (est. 2010) training portal for 100+ courses across IT, business, design, cybersecurity, accounting, and digital marketing. Supports admissions, certifications, events, and career opportunities for 150,000+ trained students.",
                'raw_notes' => "Built: educational website, course management, online admissions, events/news, student info portal, certifications showcase, SEO.\nStack: Laravel/PHP, MySQL, HTML5, CSS3, JavaScript, Bootstrap, SEO",
                'skills' => ['Laravel', 'PHP', 'MySQL', 'HTML5', 'CSS3', 'JavaScript', 'Bootstrap', 'SEO'],
                'features' => [
                    'feature' => ['Course management', 'Online admissions', 'Event & news management', 'Student information portal', 'Certification showcase'],
                    'capability' => ['Education portals', 'Admissions systems', 'Training institutes'],
                    'result' => ['Complete online presence for large-scale professional training institute'],
                ],
                'keywords' => ['education', 'admissions', 'Laravel', 'courses', 'Pakistan', 'certifications'],
            ],
        ];

        foreach ($projects as $data) {
            $project = PortfolioProject::query()->updateOrCreate(
                [
                    'title' => $data['title'],
                    'source' => 'INTERNAL',
                ],
                [
                    'upwork_account_id' => $accountId,
                    'description' => $data['description'],
                    'raw_notes' => $data['raw_notes'],
                    'website_url' => $data['website_url'],
                    'industry' => $data['industry'],
                    'project_type' => $data['project_type'],
                    'status' => 'active',
                    'visibility_internal' => true,
                    'visibility_upwork' => true,
                    'metadata' => [
                        'source_site' => 'https://hmstech.org/portfolio',
                        'external_id' => $data['external_id'],
                        'imported_from' => 'hmstech.org',
                    ],
                ]
            );

            $project->skills()->delete();
            foreach ($data['skills'] as $skill) {
                PortfolioSkill::query()->create([
                    'portfolio_project_id' => $project->id,
                    'skill' => $skill,
                ]);
            }

            $project->features()->delete();
            foreach ($data['features'] as $type => $values) {
                foreach ($values as $value) {
                    PortfolioFeature::query()->create([
                        'portfolio_project_id' => $project->id,
                        'type' => $type,
                        'value' => $value,
                    ]);
                }
            }
            foreach ($data['keywords'] as $keyword) {
                PortfolioFeature::query()->create([
                    'portfolio_project_id' => $project->id,
                    'type' => 'keyword',
                    'value' => $keyword,
                ]);
            }
        }
    }
}
