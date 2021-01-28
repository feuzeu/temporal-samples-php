<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Temporal\Samples\Child;

use Temporal\Workflow;

class ParentWorkflow implements ParentWorkflowInterface
{
    public function greet(string $name)
    {
        $child = Workflow::newChildWorkflowStub(ChildWorkflow::class);

        $childGreet = yield $child->greet($name);

        return 'Hello ' . $name . ' from parent; ' . $childGreet;
    }
}