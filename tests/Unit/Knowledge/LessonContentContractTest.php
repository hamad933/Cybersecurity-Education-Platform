<?php

namespace Tests\Unit\Knowledge;

use App\Modules\Knowledge\Content\LessonContentContract;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LessonContentContractTest extends TestCase
{
    #[Test]
    public function manifest_and_validation_share_one_registered_block_type_set(): void
    {
        $contract = new LessonContentContract;
        $manifest = $contract->manifest();

        self::assertSame(
            $contract->blockTypes(),
            array_column($manifest['block_registry'], 'type'),
        );
        self::assertSame(['published'], $manifest['revision_semantics']['learn_delivery_states']);
        self::assertSame('knowledge_unit_revision_not_independent_canonical_copy', $manifest['identity']['lesson_projection']);

        $content = $contract->validateAndNormalize(
            [['type' => 'paragraph', 'body' => 'محتوى عربي مع English و KU-D03-0001.', 'depth' => 0]],
            ['WIN-AUTH-001', 'KU-D03-0001-CLM-0001'],
        );

        self::assertSame(0, $content['blocks'][0]['depth']);
        self::assertMatchesRegularExpression('/^legacy_[0-9A-Za-z_-]{17}$/', $content['blocks'][0]['id']);
        self::assertSame(['WIN-AUTH-001', 'KU-D03-0001-CLM-0001'], $content['citations']);
        self::assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $contract->contentDigest($content['blocks'], $content['citations']),
        );
    }

    #[Test]
    public function legacy_block_identity_is_deterministic_and_explicit_v2_identity_is_preserved(): void
    {
        $contract = new LessonContentContract;
        $legacy = [['type' => 'paragraph', 'body' => 'Legacy body', 'depth' => 0]];

        $first = $contract->normalizeStoredBlocks($legacy);
        $second = $contract->normalizeStoredBlocks($legacy);
        self::assertSame($first[0]['id'], $second[0]['id']);

        $explicitId = 'AbcdEFGHijklMNOPqrstUVWX';
        $explicit = $contract->normalizeStoredBlocks([
            ['id' => $explicitId, 'type' => 'paragraph', 'body' => 'V2 body', 'depth' => 0],
        ]);
        self::assertSame($explicitId, $explicit[0]['id']);
    }

    /** @return iterable<string, array{array<mixed>, array<mixed>}> */
    public static function invalidContent(): iterable
    {
        yield 'unknown block type' => [
            [['type' => 'invented', 'body' => 'x', 'depth' => 0]],
            ['WIN-AUTH-001'],
        ];
        yield 'unsafe prose' => [
            [['type' => 'paragraph', 'body' => '<script>alert(1)</script>', 'depth' => 0]],
            ['WIN-AUTH-001'],
        ];
        yield 'depth jump' => [
            [
                ['type' => 'heading', 'body' => 'root', 'depth' => 0],
                ['type' => 'paragraph', 'body' => 'jump', 'depth' => 2],
            ],
            ['WIN-AUTH-001'],
        ];
        yield 'non-integer depth' => [
            [['type' => 'paragraph', 'body' => 'x', 'depth' => '0']],
            ['WIN-AUTH-001'],
        ];
        yield 'duplicate citation' => [
            [['type' => 'paragraph', 'body' => 'x', 'depth' => 0]],
            ['WEB-AUTH-001', 'WEB-AUTH-001'],
        ];
        yield 'invalid citation' => [
            [['type' => 'paragraph', 'body' => 'x', 'depth' => 0]],
            ['CLAIM-INVENTED'],
        ];
    }

    /**
     * @param  array<mixed>  $blocks
     * @param  array<mixed>  $citations
     */
    #[Test]
    #[DataProvider('invalidContent')]
    public function invalid_or_duplicated_content_is_rejected(array $blocks, array $citations): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new LessonContentContract)->validateAndNormalize($blocks, $citations);
    }
}
