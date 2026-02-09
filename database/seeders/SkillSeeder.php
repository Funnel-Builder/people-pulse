<?php

namespace Database\Seeders;

use App\Models\Skill;
use App\Models\SkillGroup;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $skillGroups = [
            'Frontend Development' => [
                'HTML5',
                'CSS3',
                'JavaScript (ES6+)',
                'TypeScript',
                'React.js',
                'Vue.js',
                'Angular',
                'Next.js',
                'Nuxt.js',
                'Svelte',
                'Tailwind CSS',
                'Bootstrap',
                'Sass/SCSS',
                'Webpack',
                'Vite',
                'Redux',
                'Pinia',
                'jQuery',
                'Alpine.js',
                'Blade Templates'
            ],
            'Backend Development' => [
                'PHP',
                'Laravel',
                'Node.js',
                'Express.js',
                'NestJS',
                'Python',
                'Django',
                'Flask',
                'FastAPI',
                'Ruby',
                'Ruby on Rails',
                'Java',
                'Spring Boot',
                'C#',
                '.NET Core',
                'Go (Golang)',
                'Rust',
                'GraphQL',
                'REST APIs',
                'gRPC',
                'Microservices'
            ],
            'Mobile App Development' => [
                'React Native',
                'Flutter',
                'Swift',
                'Objective-C',
                'Kotlin',
                'Java (Android)',
                'Ionic',
                'Xamarin'
            ],
            'Database & Cloud' => [
                'MySQL',
                'PostgreSQL',
                'MongoDB',
                'SQLite',
                'Redis',
                'MariaDB',
                'Oracle Database',
                'SQL Server',
                'Firebase',
                'Supabase',
                'AWS (Amazon Web Services)',
                'Google Cloud Platform (GCP)',
                'Microsoft Azure',
                'DigitalOcean',
                'Heroku',
                'Elasticsearch'
            ],
            'UI/UX Design' => [
                'Figma',
                'Adobe XD',
                'Sketch',
                'Adobe Photoshop',
                'Adobe Illustrator',
                'InVision',
                'Zeplin',
                'User Research',
                'Wireframing',
                'Prototyping',
                'Design Systems',
                'Usability Testing'
            ],
            'QA & Testing' => [
                'Manual Testing',
                'Automated Testing',
                'Selenium',
                'Cypress',
                'Playwright',
                'Jest',
                'PHPUnit',
                'Mocha',
                'Chai',
                'Appium',
                'Postman',
                'JMeter',
                'Load Testing',
                'Bug Tracking'
            ],
            'DevOps & Infrastructure' => [
                'Git',
                'GitHub',
                'GitLab',
                'Bitbucket',
                'Docker',
                'Kubernetes',
                'Jenkins',
                'GitHub Actions',
                'GitLab CI/CD',
                'Terraform',
                'Ansible',
                'Linux Administration',
                'Nginx',
                'Apache',
                'Bash Scripting'
            ],
            'Project Management' => [
                'Agile Methodology',
                'Scrum',
                'Kanban',
                'Jira',
                'Trello',
                'Asana',
                'ClickUp',
                'Notion',
                'Confluence',
                'Slack',
                'Microsoft Teams',
                'Product Management'
            ],
            'Content & Marketing' => [
                'SEO (Search Engine Optimization)',
                'Content Writing',
                'Copywriting',
                'Social Media Marketing',
                'Email Marketing',
                'Google Analytics',
                'WordPress Management',
                'Technical Writing',
                'Blogging',
                'Video Editing',
                'Graphic Design'
            ],
            'HR & Administration' => [
                'Recruitment',
                'Talent Acquisition',
                'Employee Relations',
                'Performance Management',
                'Payroll Management',
                'HR Policy Development',
                'Onboarding',
                'Training & Development',
                'Conflict Resolution',
                'Office Management'
            ],
            'CMS & E-commerce' => [
                'WordPress',
                'WooCommerce',
                'Shopify',
                'Magento',
                'Drupal',
                'Joomla',
                'Wix',
                'Squarespace',
                'PrestaShop'
            ],
            'Data Science & AI' => [
                'Machine Learning',
                'Artificial Intelligence',
                'Data Analysis',
                'Pandas',
                'NumPy',
                'Scikit-learn',
                'TensorFlow',
                'PyTorch',
                'Data Visualization',
                'Tableau',
                'Power BI'
            ],
            'Other Tools' => [
                'Microsoft Office Suite',
                'Google Workspace',
                'Zoom',
                'Salesforce',
                'HubSpot',
                'Zendesk'
            ]
        ];

        foreach ($skillGroups as $groupName => $skills) {
            $group = SkillGroup::firstOrCreate(['name' => $groupName]);

            foreach ($skills as $skillName) {
                Skill::firstOrCreate([
                    'name' => $skillName,
                    'skill_group_id' => $group->id
                ]);
            }
        }
    }
}
