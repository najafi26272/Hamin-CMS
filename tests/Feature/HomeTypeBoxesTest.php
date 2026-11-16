<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTypeBoxesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_has_a_box_for_each_content_type(): void
    {
        $response = $this->get('/')->assertOk();

        foreach (['visual', 'audio', 'video', 'poem'] as $type) {
            $response->assertSee('href="'.route('site.posts', ['type' => $type]).'"', false);
        }

        $response->assertSeeInOrder(['لوح', 'شنیداری', 'تماشایی', 'شعر']);
    }
}
