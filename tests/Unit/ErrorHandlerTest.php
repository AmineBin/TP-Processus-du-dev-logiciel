<?php

namespace Tests\Unit;

use App\Support\ErrorHandler;
use PHPUnit\Framework\TestCase;

class ErrorHandlerTest extends TestCase
{
    public function test_every_catalogued_code_resolves_to_its_message(): void
    {
        foreach (ErrorHandler::all() as $code => $message) {
            $this->assertNotEmpty($message);
            $this->assertSame($message, ErrorHandler::message($code));
        }
    }

    public function test_unknown_codes_return_a_safe_fallback(): void
    {
        $this->assertSame(ErrorHandler::message('UNKNOWN_ERROR'), ErrorHandler::message('UNKNOWN_CODE'));
    }
}
