<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Message\Command;

/**
 * Implemented by every command the bundle dispatches, so they can be routed as a group:
 *
 * framework:
 *     messenger:
 *         routing:
 *             'Setono\MetaConversionsApiBundle\Message\Command\CommandInterface': async
 */
interface CommandInterface
{
}
