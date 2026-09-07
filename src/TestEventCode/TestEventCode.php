<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\TestEventCode;

/**
 * Names shared by the subscribers that store and read the test event code
 *
 * See https://developers.facebook.com/docs/marketing-api/conversions-api/using-the-api#testEvents
 */
final class TestEventCode
{
    public const SESSION_KEY = 'smca_test_event_code';

    /**
     * The query parameters that set (or, when empty, clear) the test event code
     *
     * @var list<string>
     */
    public const QUERY_PARAMETERS = ['_testEventCode', '_test_event_code'];

    private function __construct()
    {
    }
}
