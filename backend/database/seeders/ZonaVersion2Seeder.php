<?php

namespace Database\Seeders;

use App\Models\ZonaVersion2;
use Illuminate\Database\Seeder;

class ZonaVersion2Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $arrayData = [
            ['id' => '1', 'codigo' => '01', 'nombre' => 'Rural', 'descripcion' => null, 'habilitado' => 'SI', 'aplicacion' => null, 'isStandardGEL' => 'False', 'isStandardMSPS' => 'False', 'extra_I' => null, 'extra_II' => null, 'extra_III' => null, 'extra_IV' => null, 'extra_V' => null, 'extra_VI' => null, 'extra_VII' => null, 'extra_VIII' => null, 'extra_IX' => null, 'extra_X' => null, 'valorRegistro' => null, 'usuarioResponsable' => null, 'fecha_Actualizacion' => '2022-06-16 04:04:43 PM', 'isPublicPrivate' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => '2', 'codigo' => '02', 'nombre' => 'Urbano', 'descripcion' => null, 'habilitado' => 'SI', 'aplicacion' => null, 'isStandardGEL' => 'False', 'isStandardMSPS' => 'False', 'extra_I' => null, 'extra_II' => null, 'extra_III' => null, 'extra_IV' => null, 'extra_V' => null, 'extra_VI' => null, 'extra_VII' => null, 'extra_VIII' => null, 'extra_IX' => null, 'extra_X' => null, 'valorRegistro' => null, 'usuarioResponsable' => null, 'fecha_Actualizacion' => '2022-06-16 04:04:43 PM', 'isPublicPrivate' => null, 'created_at' => null, 'updated_at' => null],
        ];

        // Inicializar la barra de progreso
        $this->command->info('Starting Seed Data ...');
        $bar = $this->command->getOutput()->createProgressBar(count($arrayData));

        foreach ($arrayData as $key => $value) {
            $data = new ZonaVersion2;
            $data->codigo = $value['codigo'];
            $data->nombre = $value['nombre'];
            $data->descripcion = $value['descripcion'];
            $data->habilitado = $value['habilitado'];
            $data->aplicacion = $value['aplicacion'];
            $data->isStandardGEL = $value['isStandardGEL'];
            $data->isStandardMSPS = $value['isStandardMSPS'];
            $data->extra_I = $value['extra_I'];
            $data->extra_II = $value['extra_II'];
            $data->extra_III = $value['extra_III'];
            $data->extra_IV = $value['extra_IV'];
            $data->extra_V = $value['extra_V'];
            $data->extra_VI = $value['extra_VI'];
            $data->extra_VII = $value['extra_VII'];
            $data->extra_VIII = $value['extra_VIII'];
            $data->extra_IX = $value['extra_IX'];
            $data->extra_X = $value['extra_X'];
            $data->valorRegistro = $value['valorRegistro'];
            $data->usuarioResponsable = $value['usuarioResponsable'];
            $data->fecha_Actualizacion = $value['fecha_Actualizacion'];
            $data->isPublicPrivate = $value['isPublicPrivate'];
            $data->save();
            $bar->advance();
        }

        $bar->finish(); // Finalizar la barra
    }
}
