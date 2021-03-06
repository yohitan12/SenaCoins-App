<?php 

	/**
	* 
	*/
	class mdlProducto
	{
		private $_idProducto;
		private $_idEquipo;
		private $_nombre_producto;
		private $_descripcion;
		private $_url_guia;
		private $_administrador;
		private $_monedas;	
		private $db;

		public function __GET($key){
			return $this->$key;
		}

		public function __SET($key, $value){
			$this->$key = $value;
		}
		
		function __construct($db)
		{
			try {
				$this->db = $db;
			} catch (PDOException $e) {
				exit('Database connection could not be established.');
			}
		}

		public function Guardar(){
			$gsent = $this->db->prepare("call SP_GuardarProducto(:np, :d, :ie)");

			$gsent->bindValue(":np", $this->__GET("_nombre_producto"), PDO::PARAM_STR);
			$gsent->bindValue(":d", $this->__GET("_descripcion"), PDO::PARAM_STR);
			$gsent->bindValue(":ie", $this->__GET("_idEquipo"), PDO::PARAM_INT);

			$stm = $gsent->execute();

			return $stm;
		}

		public function generarTransaccion()
		{
			$stmt = $this->db->prepare('INSERT INTO transaccion(cantidad,producto_idProducto,equipo_idEquipo)VALUES(?, ?, ?)');
			$stmt2 = $this->db->prepare('UPDATE equipo SET monedas = (monedas - ?) WHERE idEquipo = ?;');

			try 
			{
				$this->db->beginTransaction();

				$stmt->bindValue(1, $this->__GET("_monedas"), PDO::PARAM_INT);
				$stmt->bindValue(2, $this->__GET("_idProducto"), PDO::PARAM_INT);
				$stmt->bindValue(3, $_SESSION["ID"], PDO::PARAM_INT);
				$stmt->execute();

				$stmt2->bindValue(1, $this->__GET("_monedas"), PDO::PARAM_INT); 
				$stmt2->bindValue(2, $_SESSION["ID"], PDO::PARAM_INT);
				$stmt2->execute();

				$this->db->commit();
			}
			catch (PDOException $e) {
				$this->db->rollBack();
				var_dump($e);
				exit();
			}
		}


		public function Modificar(){
			$gsent = $this->db->prepare("call SP_MdificarProducto(:np, :d, :idP)");

			$gsent->bindValue(":np", $this->__GET("_nombre_producto"), PDO::PARAM_STR);
			$gsent->bindValue(":d", $this->__GET("_descripcion"), PDO::PARAM_STR);
			$gsent->bindValue(":idP", $this->__GET("_idProducto"), PDO::PARAM_INT);

			$stm = $gsent->execute();

			return $stm;
		}

		public function Listar(){
			$stm = $this->db->prepare("call SP_ListarProducto()");

			$stm->execute();

			return $stm->fetchAll();
		}

		public function Listar_Detalle_Artefacto(){
			$sql = "SELECT (select sum(t.cantidad) from transaccion as t where t.producto_idProducto = p.idProducto) as coins, p.*, e.nombre_equipo, e.idEquipo FROM senacoins.producto as p inner join equipo as e on p.equipo_idEquipo = e.idEquipo WHERE p.idProducto = ?";
			$query = $this->db->prepare($sql);
			$query->bindValue(1, $this->__GET("_idProducto"), PDO::PARAM_INT);
			$query->execute();
			return $query->fetch();
		}

		public function Listar_Imagenes_Asociadas(){
			$sql = "select * from imagen WHERE producto_idProducto = ? order by estado desc";
			$query = $this->db->prepare($sql);
			$query->bindValue(1, $this->__GET("_idProducto"), PDO::PARAM_INT);
			$query->execute();
			return $query->fetchAll();
		}

		public function login(){
			$gsent = $this->db->prepare("CALL SP_Login(:nu)");
			$gsent->bindValue(":nu",$this->__GET("_nombre_usuario"));
			$gsent->execute();
			return $gsent->fetch();
		}

		public function Listar_Equipos_Producto(){
			$sql = "SELECT e.idEquipo as idEquipo, e.nombre_equipo as nombre_equipo FROM equipo e INNER JOIN producto p on (e.idEquipo = p.equipo_idEquipo)";
			$query = $this->db->prepare($sql);
			$query->execute();
			return $query->fetchAll();
		}



		public function monedasRestantes(){
			$gsent = $this->db->prepare("SELECT monedas FROM equipo WHERE idEquipo = ?");
			$gsent->bindValue(1 ,$this->__GET("_idEquipo"));
			$gsent->execute();
			return $gsent->fetch();
		}

		public function productoInvertidos(){
			$query = $this->db->prepare("SELECT p.nombre_producto as nombre_producto, i.url_imagen as url_imagen, m.cantidad as cantidad FROM transaccion m INNER JOIN producto p ON (p.idProducto = m.producto_idProducto) INNER JOIN imagen i ON (p.idProducto = i.producto_idProducto and i.estado = 1) WHERE m.equipo_idEquipo = ?");
			$query->bindValue(1 ,$this->__GET("_idEquipo"));
			$query->execute();
			return $query->fetchAll();
		}

	}

	?>