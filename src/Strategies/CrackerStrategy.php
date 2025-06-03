<?php

namespace Admin\NewCracker\Strategies;

interface CrackerStrategy {
    public function crack(): array;
}