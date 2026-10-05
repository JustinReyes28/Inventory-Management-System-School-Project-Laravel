<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_a_guest_is_sent_to_the_login_page_from_the_root_path(): void
    {
        $this->get('/')->assertRedirect('/login');
    }
}
