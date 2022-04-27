<?php

namespace is\Masters\Modules\Isengine\Form;

use is\Helpers\System;
use is\Helpers\Objects;
use is\Helpers\Parser;
use is\Components\State;

$state = State::getInstance();
$state->set(
    'form',
    Parser::toJson(
        Objects::remap(
            $this->settings['data'],
            'name'
        )
    )
);
