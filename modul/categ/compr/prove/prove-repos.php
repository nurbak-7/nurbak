<?php
/* ============================================================
   Ruta: modul/categ/compr/prove/prove-repos.php
   Módulo: Compras / Proveedores

   RESPONSABILIDAD:
   Acceder exclusivamente a compr.prove.

   NO HACE:
   - No recibe HTTP.
   - No genera HTML ni JSON.
   - No abre conexiones.
   - No consulta productos ni inventario.
============================================================ */
declare(strict_types=1);

final class ProvRepo
{
    public function __construct(private PDO $db) {}

    /* --------------------------------------------------------
       LISTAR PROVEEDORES
       La búsqueda usa parámetros distintos para cada columna,
       porque la conexión trabaja con prepares nativos.
    -------------------------------------------------------- */
    public function listar(string $buscar = '', int $limite = 50): array
    {
        $limite = max(1, min($limite, 100));
        $buscar = trim($buscar);
        $sql = 'SELECT id, razon, ruc, conta, telef, email, direc, estad, fecre FROM prove';
        $params = [];

        if ($buscar !== '') {
            $sql .= ' WHERE razon LIKE :razon OR ruc LIKE :ruc OR conta LIKE :conta OR telef LIKE :telef OR email LIKE :email OR direc LIKE :direc';
            $valor = '%' . $buscar . '%';
            foreach (['razon','ruc','conta','telef','email','direc'] as $p) $params[$p] = $valor;
        }
        $sql .= " ORDER BY id DESC LIMIT {$limite}";

        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $n=>$v) $stmt->bindValue(':' . $n, $v, PDO::PARAM_STR);
        $stmt->execute();
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $r;
    }

    /* --------------------------------------------------------
       REGISTRAR PROVEEDOR
    -------------------------------------------------------- */
    public function agregar(array $datos): int
    {
        $razon = trim((string)($datos['razon'] ?? ''));
        if ($razon === '') throw new InvalidArgumentException('La razón social es obligatoria.');

        $stmt = $this->db->prepare(
            'INSERT INTO prove (razon,ruc,conta,telef,email,direc) VALUES (:razon,:ruc,:conta,:telef,:email,:direc)'
        );
        $stmt->execute([
            'razon'=>$razon,
            'ruc'=>self::nulo($datos['ruc'] ?? null),
            'conta'=>self::nulo($datos['conta'] ?? null),
            'telef'=>self::nulo($datos['telef'] ?? null),
            'email'=>self::nulo($datos['email'] ?? null),
            'direc'=>self::nulo($datos['direc'] ?? null),
        ]);
        $id=(int)$this->db->lastInsertId();
        
        return $id;
    }

    /* --------------------------------------------------------
       ACTIVAR / DESACTIVAR
    -------------------------------------------------------- */
    public function estad(int $id,int $estad): bool
    {
        if ($id<=0) throw new InvalidArgumentException('Proveedor no válido.');
        $estad=$estad===1?1:0;
        $stmt=$this->db->prepare('UPDATE prove SET estad=:estad WHERE id=:id');
        $stmt->execute(['estad'=>$estad,'id'=>$id]);
        $ok=$stmt->rowCount()>0;
        
        return $ok;
    }

    private static function nulo(mixed $valor): ?string
    {
        $valor=trim((string)($valor ?? ''));
        return $valor===''?null:$valor;
    }
}
