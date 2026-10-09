<?php

namespace Tests\Unit\Guests;

use App\Domain\Guests\GuestListCsv;
use App\Enums\GuestCategory;
use App\Enums\RsvpStatus;
use App\Models\Guest;
use InvalidArgumentException;
use Tests\TestCase;

class GuestListCsvTest extends TestCase
{
    public function test_it_reads_a_semicolon_file_with_list_labels(): void
    {
        $csv = "nome;telefone;email;instagram;lista;rsvp;acompanhantes;observacao\n".
            "Lia;119999;lia@festa.test;@lia;VIP;Confirmado;2;mesa\n";

        $rows = (new GuestListCsv)->parse($csv);

        $this->assertCount(1, $rows);
        $this->assertSame('Lia', $rows[0]->name);
        $this->assertSame('119999', $rows[0]->phone);
        $this->assertSame('lia@festa.test', $rows[0]->email);
        $this->assertSame('@lia', $rows[0]->instagram);
        $this->assertSame(GuestCategory::Vip, $rows[0]->category);
        $this->assertSame(RsvpStatus::Confirmed, $rows[0]->rsvp);
        $this->assertSame(2, $rows[0]->plusOnes);
        $this->assertSame('mesa', $rows[0]->notes);
    }

    public function test_it_reads_a_comma_file_and_leaves_an_unknown_list_empty(): void
    {
        $csv = "name,e-mail,category,status\nNico,nico@festa.test,backstage,sent\n";

        $rows = (new GuestListCsv)->parse($csv);

        $this->assertSame('Nico', $rows[0]->name);
        $this->assertSame('nico@festa.test', $rows[0]->email);
        $this->assertNull($rows[0]->category);
        $this->assertSame(RsvpStatus::Sent, $rows[0]->rsvp);
    }

    public function test_it_rejects_a_file_without_a_name_column(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new GuestListCsv)->parse("email\na@b.test\n");
    }

    public function test_it_writes_the_list_with_a_header_excel_can_open(): void
    {
        $guest = new Guest([
            'name' => 'Lia',
            'email' => 'lia@festa.test',
            'plus_ones' => 1,
        ]);
        $guest->category = GuestCategory::Press;
        $guest->rsvp_status = RsvpStatus::Confirmed;

        $csv = (new GuestListCsv)->render([$guest]);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('nome;telefone;email;instagram;lista;rsvp;acompanhantes;observacao', $csv);
        $this->assertStringContainsString('Lia;;lia@festa.test;;Imprensa;Confirmado;1;', $csv);
    }
}
