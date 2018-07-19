<?php

namespace SudiptoChoudhury\Support\Forge\Api\Traits\Import;

use SudiptoChoudhury\Support\Utils\Traits\Dirs;

trait AllTraits
{
    use Dirs;
    use Getters;
    use Parsers;
    use Writers;
    use Filterable;
    use DefaultFilters;
}