<?php

namespace App\Console\Commands;

use App\Services\AI\MessageMatchingService;
use Illuminate\Console\Command;


//Todo move this console command to the tests
class TestGeminiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-gemini {--text= : The text of the vacancy to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Gemini MessageMatchingService extraction';

    /**
     * Execute the console command.
     */
    public function handle(MessageMatchingService $service)
    {
        $text = $this->option('text');

        if (!$text) {
            $text = "#vacancy #marketing #IT #remote
Sardina Systems is an OpenInfra software platform company based on OpenStack, Kubernetes and Ceph. Behind our technology is a distributed team of builders — and we need a Marketing Manager who can bring that story to the world. We're looking for someone hands-on and organized, who can run social media channels, design clean visuals, write a sharp newsletter, and coordinate with partners without dropping the ball. You're not precious about doing the execution yourself, and you know how to keep external relationships moving.
What You'll Be Doing:
Manage and grow Sardina's presence on LinkedIn, Twitter/X, and other relevant platforms — content planning, posting, community engagement
Design simple graphics, banners, and infographics using tools like Figma
Build, schedule, and analyze email newsletters and campaigns in Mailchimp
Coordinate co-marketing initiatives with technology partners, resellers, and community organizations — managing timelines, deliverables, and communication
Support event participation — from CFP and materials to follow-up campaigns
Track performance metrics and report on what's working
What You'll Need:
2–4 years of experience in marketing, communications, or a related role
Hands-on experience with Mailchimp or a comparable email platform
Basic visual design skills — you can produce clean, on-brand assets independently
Strong organizational skills and the ability to manage multiple external stakeholders
Good written English and clear communication style
Experience with or genuine interest in B2B tech environments
Event coordination experience is a plus
Based in or willing to work within European time zones (CET/EET)
We offer:
A real scope of ownership from day one in a lean, fast-moving team
Fully remote work with flexible hours
Exposure to the global OpenInfra and cloud infrastructure community
Competitive compensation with regular review
Opportunity to attend and support industry events
Ready to get your hands on something real? Reach out.";
            $this->info("Используем дефолтный текст вакансии:\n$text\n");
        }

        $this->info('Отправляем запрос к Gemini...');

        $startTime = microtime(true);
        $result = $service->extractEntities($text);
        $endTime = microtime(true);

        $duration = round($endTime - $startTime, 2);

        if ($result === null) {
            $this->error("Ошибка: сервис вернул null. Проверьте GEMINI_API_KEY в .env и логи (storage/logs/laravel.log).");
            return Command::FAILURE;
        }

        $this->info("Запрос выполнен за {$duration} сек.");
        $this->line('');
        $this->info('Результат извлечения:');
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return Command::SUCCESS;
    }
}
