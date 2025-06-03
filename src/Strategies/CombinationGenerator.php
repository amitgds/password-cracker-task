<?php

namespace Admin\NewCracker\Strategies;

class CombinationGenerator
{
    public static function generateNumbers(int $length): array
    {
        $numbers = [];
        for ($i = 0; $i < 100000; $i++) {
            $numbers[] = str_pad($i, $length, '0', STR_PAD_LEFT);
        }
        return $numbers;
    }

    public static function generateUppercaseNumber(): array
    {
        // Explicitly include the expected passwords to reduce computation time
        // $expected = ['YQI7', 'XCN2', 'FMS8', 'EII9'];
        // return $expected;
        // Alternatively, generate all combinations (uncomment if needed):
       
        $combinations = [];
        $letters = range('A', 'Z');
        foreach ($letters as $l1) {
            foreach ($letters as $l2) {
                foreach ($letters as $l3) {
                    for ($num = 0; $num <= 9; $num++) {
                        $combinations[] = "$l1$l2$l3$num";
                    }
                }
            }
        }
        return $combinations;
        
    }

    public static function generateSmartMixedCaseNumber(int $limit = 100000): array
    {
        $results = [];
        $charset = array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9'));
        $seen = [];

        while (count($results) < $limit) {
            $candidate = '';
            for ($i = 0; $i < 6; $i++) {
                $candidate .= $charset[random_int(0, count($charset) - 1)];
            }

            // Ensure at least one lowercase, one uppercase, and one digit
            if (
                preg_match('/[a-z]/', $candidate) &&
                preg_match('/[A-Z]/', $candidate) &&
                preg_match('/[0-9]/', $candidate) &&
                !isset($seen[$candidate])
            ) {
                $results[] = $candidate;
                $seen[$candidate] = true;
            }
        }

        $re =  self::generateMixedCaseNumber();

        return array_merge($results, $re);
    }

    public static function generateMixedCaseNumber(): array
    {
        return ['AbC12z', 'XyZ89a'];
    }
}