<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function testRootRedirectsToTheProtectedTestPage()
    {
        $response = $this->get('/');

        $response->assertRedirect(route('personas-rapidas.prueba'));
    }

    public function testPersonaApiRequiresAuthentication()
    {
        $this->getJson('/api/personas-rapidas/buscar?rut=12345678-5')
            ->assertUnauthorized();

        $this->postJson('/api/personas-rapidas/guardar', [])
            ->assertUnauthorized();
    }
}
