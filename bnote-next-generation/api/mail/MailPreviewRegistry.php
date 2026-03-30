<?php
declare(strict_types=1);

require_once __DIR__ . '/MailPreviewFixtures.php';
require_once __DIR__ . '/builders/PasswordResetMailBuilder.php';
require_once __DIR__ . '/builders/NewUserAdminMailBuilder.php';
require_once __DIR__ . '/builders/LongDemoMailBuilder.php';
require_once __DIR__ . '/builders/CommentDiscussionMailBuilder.php';
require_once __DIR__ . '/NextGenMailMessage.php';

final class MailPreviewRegistry {
    /** @return list<array{id: string, label: string}> */
    public static function templates(): array {
        return [
            ['id' => 'password_reset', 'label' => 'Password reset'],
            ['id' => 'new_user_admin', 'label' => 'New user (admin notification)'],
            ['id' => 'long_demo', 'label' => 'Long layout demo (lorem)'],
            ['id' => 'comment_discussion_rehearsal_short', 'label' => 'Comment discussion (rehearsal, short thread)'],
            ['id' => 'comment_discussion_rehearsal_long', 'label' => 'Comment discussion (rehearsal, long thread)'],
            ['id' => 'comment_discussion_concert', 'label' => 'Comment discussion (concert, short thread)'],
            ['id' => 'comment_discussion_vote', 'label' => 'Comment discussion (vote)'],
            ['id' => 'comment_discussion_single_new', 'label' => 'Comment discussion (single new message)'],
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
                    [],
                    MailPreviewFixtures::recipientPreviewFirstName()
                );
            case 'long_demo':
                return LongDemoMailBuilder::build(
                    $sd,
                    $locale,
                    MailPreviewFixtures::previewToEmail(),
                    MailPreviewFixtures::recipientPreviewFirstName()
                );
            case 'comment_discussion_rehearsal_short':
                $ctxShort = MailPreviewFixtures::commentDiscussionRehearsalShort($locale);
                $ctxShort['recipientFirstName'] = MailPreviewFixtures::recipientPreviewFirstName();

                return CommentDiscussionMailBuilder::buildForPreview(
                    $sd,
                    $locale,
                    $ctxShort,
                    [MailPreviewFixtures::previewToEmail()],
                    []
                );
            case 'comment_discussion_rehearsal_long':
                $ctxLong = MailPreviewFixtures::commentDiscussionRehearsalLong($locale);
                $ctxLong['recipientFirstName'] = MailPreviewFixtures::recipientPreviewFirstName();

                return CommentDiscussionMailBuilder::buildForPreview(
                    $sd,
                    $locale,
                    $ctxLong,
                    [MailPreviewFixtures::previewToEmail()],
                    []
                );
            case 'comment_discussion_concert':
                $ctxConcert = MailPreviewFixtures::commentDiscussionConcert($locale);
                $ctxConcert['recipientFirstName'] = MailPreviewFixtures::recipientPreviewFirstName();

                return CommentDiscussionMailBuilder::buildForPreview(
                    $sd,
                    $locale,
                    $ctxConcert,
                    [MailPreviewFixtures::previewToEmail()],
                    []
                );
            case 'comment_discussion_vote':
                $ctxVote = MailPreviewFixtures::commentDiscussionVote($locale);
                $ctxVote['recipientFirstName'] = MailPreviewFixtures::recipientPreviewFirstName();

                return CommentDiscussionMailBuilder::buildForPreview(
                    $sd,
                    $locale,
                    $ctxVote,
                    [MailPreviewFixtures::previewToEmail()],
                    []
                );
            case 'comment_discussion_single_new':
                $ctxSingle = MailPreviewFixtures::commentDiscussionSingleNew($locale);
                $ctxSingle['recipientFirstName'] = MailPreviewFixtures::recipientPreviewFirstName();

                return CommentDiscussionMailBuilder::buildForPreview(
                    $sd,
                    $locale,
                    $ctxSingle,
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
