<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function test_it_validates_correct_data(): void
    {
        $data = [
            'username' => 'admin',
            'age' => 25
        ];

        $validator = new Validator($data, [
            'username' => 'required|min:3',
            'age' => 'required|int'
        ]);

        $this->assertTrue($validator->validate());
        $this->assertEmpty($validator->errors());
    }

    public function test_it_fails_on_required_field(): void
    {
        $data = [];

        $validator = new Validator($data, [
            'username' => 'required'
        ]);

        $this->assertFalse($validator->validate());
        $this->assertArrayHasKey('username', $validator->errors());
        $this->assertEquals('The username field is required.', $validator->errors()['username']);
    }

    public function test_it_fails_on_min_length(): void
    {
        $data = ['username' => 'yo'];

        $validator = new Validator($data, [
            'username' => 'min:3'
        ]);

        $this->assertFalse($validator->validate());
        $this->assertEquals('The username must be at least 3 characters.', $validator->errors()['username']);
    }

    public function test_it_validates_integer_correctly(): void
    {
        $validator = new Validator(['age' => 'not_a_number'], ['age' => 'int']);
        $this->assertFalse($validator->validate());

        $validator = new Validator(['age' => '123'], ['age' => 'int']);
        $this->assertTrue($validator->validate());
    }

    public function test_it_validates_string_type(): void
    {
        $validator = new Validator(['description' => 'Just text'], ['description' => 'string']);
        $this->assertTrue($validator->validate(), 'String input should pass');

        $validator = new Validator(['description' => 12345], ['description' => 'string']);
        $this->assertFalse($validator->validate(), 'Integer input should fail');
        $this->assertEquals('The description must be a string.', $validator->errors()['description']);

        $validator = new Validator(['description' => ['some', 'array']], ['description' => 'string']);
        $this->assertFalse($validator->validate(), 'Array input should fail');
    }
}