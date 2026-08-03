<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\Inventaire;
use Modules\Stock\Models\Sortie;
use Modules\Stock\Models\Transfert;
use Modules\Stock\Services\NumerotationService;
use Tests\TestCase;

class NumerotationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NumerotationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(NumerotationService::class);
    }

    public function test_invariant_I8_sequence_continue_sans_collision(): void
    {
        $annee = now()->year;
        $numeros = [];

        foreach (range(1, 20) as $i) {
            $numeros[] = $this->service->attribuer(Entree::factory()->create());
        }

        // 20 numéros distincts, séquence continue de 0001 à 0020
        $this->assertCount(20, array_unique($numeros));
        $this->assertSame(
            array_map(fn (int $i) => sprintf('ENT-%d-%04d', $annee, $i), range(1, 20)),
            $numeros
        );
    }

    public function test_invariant_I8_numerotation_independante_par_table(): void
    {
        $annee = now()->year;

        $this->assertSame("ENT-{$annee}-0001", $this->service->attribuer(Entree::factory()->create()));
        $this->assertSame("SOR-{$annee}-0001", $this->service->attribuer(Sortie::factory()->create()));
        $this->assertSame("TRF-{$annee}-0001", $this->service->attribuer(Transfert::factory()->create()));
        $this->assertSame("INV-{$annee}-0001", $this->service->attribuer(Inventaire::factory()->create()));
        $this->assertSame("ENT-{$annee}-0002", $this->service->attribuer(Entree::factory()->create()));
    }

    public function test_invariant_I8_remise_a_un_au_changement_d_annee(): void
    {
        $annee = now()->year;
        $this->service->attribuer(Entree::factory()->create());
        $this->service->attribuer(Entree::factory()->create());

        Carbon::setTestNow(now()->addYear());

        try {
            $this->assertSame(
                sprintf('ENT-%d-0001', $annee + 1),
                $this->service->attribuer(Entree::factory()->create())
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_le_numero_est_enregistre_sur_le_document(): void
    {
        $entree = Entree::factory()->create();
        $numero = $this->service->attribuer($entree);

        $this->assertSame($numero, $entree->fresh()->numero);
        $this->assertSame($numero, $entree->fresh()->numero_affiche);
    }

    public function test_invariant_I8_attribution_sous_verrou(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Verrou FOR UPDATE observable sur PostgreSQL uniquement (no-op SQLite).');
        }

        DB::enableQueryLog();
        $this->service->attribuer(Entree::factory()->create());
        $requetes = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        $this->assertTrue(
            $requetes->contains(fn (string $sql) => str_contains($sql, 'stock_sequences') && str_contains(strtolower($sql), 'for update')),
            'La lecture de la séquence doit être verrouillante (S4).'
        );
    }

    public function test_document_inconnu_refuse(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->attribuer(\Modules\Stock\Models\Niveau::factory()->create());
    }
}
