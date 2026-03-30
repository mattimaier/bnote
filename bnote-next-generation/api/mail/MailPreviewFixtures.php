<?php
declare(strict_types=1);

require_once __DIR__ . '/MailEnv.php';

final class MailPreviewFixtures {
    public static function systemData(): object {
        return new class () {
            public function getCompany(): string {
                return 'Demo Band';
            }
        };
    }

    /** @return array{userId:int,contactId:int,name:string,surname:string,email:string,login:string,autoUserActivation:bool} */
    public static function newUserCtx(): array {
        return [
            'userId' => 42,
            'contactId' => 7,
            'name' => 'Jana',
            'surname' => 'Example',
            'email' => 'jana@example.com',
            'login' => 'jana@example.com',
            'autoUserActivation' => false,
        ];
    }

    public static function previewToEmail(): string {
        return 'preview@example.com';
    }

    public static function demoResetUrl(): string {
        $base = MailEnv::nextgenPublicBaseUrl();
        if ($base !== '') {
            return $base . '/reset-password/confirm/?token=demo-token-preview';
        }
        return 'https://example.org/bnote-next-generation/reset-password/confirm/?token=demo-token-preview';
    }
}
