<?php
/* ============================================================
   Ruta: modul/admin/secue/secue-repos.php
   Función: Administrar las secuencias de usuarios.

   SECU utiliza la tabla actual `seque`:
   - tipo
   - ultim

   La numeración de TIEND pertenece a `tisec`.
============================================================ */

declare(strict_types=1);

final class SecuRepo
{
    public function __construct(private PDO $db) {}

    public function ultimo(string $tipo): int
    {
        $stmt = $this->db->prepare('SELECT ultim FROM seque WHERE tipo = :tipo LIMIT 1');
        $stmt->execute([':tipo' => $tipo]);
        $valor = $stmt->fetchColumn();
        if ($valor === false) throw new RuntimeException("No existe la secuencia {$tipo}.");
        return (int) $valor;
    }

    public function primerLibre(string $tipo): int
    {
        $tipo = strtoupper(trim($tipo));
        $columna = match ($tipo) {
            'ADMINISTRADOR' => 'admic',
            'OPERADOR' => 'operc',
            default => throw new InvalidArgumentException('Tipo de secuencia no válido.'),
        };

        $stmt = $this->db->query("SELECT {$columna} FROM usuar WHERE {$columna} IS NOT NULL");
        $usados = [];
        while (($valor = $stmt->fetchColumn()) !== false) {
            $codig = (string) $valor;
            $numer = $tipo === 'ADMINISTRADOR'
                ? (int) preg_replace('/^A/i', '', $codig)
                : (int) $codig;
            if ($numer > 0) $usados[$numer] = true;
        }

        $numer = 1;
        while (isset($usados[$numer])) $numer++;
        return $numer;
    }

    public function reservar(string $tipo): int
    {
        $tipo = strtoupper(trim($tipo));
        $stmt = $this->db->prepare('SELECT ultim FROM seque WHERE tipo = :tipo FOR UPDATE');
        $stmt->execute([':tipo' => $tipo]);
        $valor = $stmt->fetchColumn();
        if ($valor === false) throw new RuntimeException("No existe la secuencia {$tipo}.");

        $numer = $this->primerLibre($tipo);
        if ($numer > (int) $valor) {
            $actualizar = $this->db->prepare('UPDATE seque SET ultim = :ultim WHERE tipo = :tipo');
            $actualizar->execute([':ultim' => $numer, ':tipo' => $tipo]);
        }
        return $numer;
    }
}
