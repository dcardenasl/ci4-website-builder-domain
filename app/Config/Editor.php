<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

final class Editor extends BaseConfig
{
    public int $maxPayloadBytes = 1048576;
    public int $maxBlocks = 500;
    public int $maxOperations = 1000;
    public int $maxDepth = 16;
}
