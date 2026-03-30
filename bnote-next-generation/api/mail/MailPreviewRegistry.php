<?php
declare(strict_types=1);

require_once __DIR__ . '/MailPreviewFixtures.php';
require_once __DIR__ . '/builders/PasswordResetMailBuilder.php';
require_once __DIR__ . '/builders/NewUserAdminMailBuilder.php';
require_once __DIR__ . '/NextGenMailMessage.php';

final class MailPreviewRegistry {
    /** @return list<array{id: string, label: string}> */
    public static function templates(): array {
        return [
            ['id' => 'password_reset', 'label' => 'Password reset'],
            ['id' => 'new_user_admin', 'label' => 'New user (admin notification)'],
        ];
    }

    public static function build(string $templateId, string $locale): NextGenMailMessage {
        $sd = MailPreviewFixtures::systemData();
        switch ($templateId) {
            case 'password_reset':
                return PasswordResetMailBuilder::build(
                    $sd,
                    $locale,
                    MailPreviewFixtures::previewToEmail(),
                    MailPreviewFixtures::demoResetUrl()
                );
            case 'new_user_admin':
                return NewUserAdminMailBuilder::build(
                    $sd,
                    $locale,
                    MailPreviewFixtures::newUserCtx(),
                    [MailPreviewFixtures::previewToEmail()],
                    []
                );
            default:
                throw new InvalidArgumentException('Unknown template: ' . $templateId);
        }
    }

    public static function isValidTemplate(string $id): bool {
        foreach (self::templates() as $t) {
            if ($t['id'] === $id) {
                return true;
            }
        }
        return false;
    }
}
