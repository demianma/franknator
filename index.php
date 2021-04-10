<?php
function console_log( $data ){
  echo '<script>';
  echo 'console.log('. json_encode( $data ) .')';
  echo '</script>';
}



class Image {
	#converte imagem em base64 para ser usado como src nos <img>
	public static function convertToBase64($image){
		ob_start();
		imagepng($image);
        $data = ob_get_contents();
        ob_end_clean();
		
        if( !empty( $data ) ) {
			$data = base64_encode( $data );
			// Check for base64 errors
			if ( $data !== false ) {
				// Success
				return $data;
			}
		}
		console_log("*** ERRO convertendo Base64");
		return "";
	}
}



#quadrinhos das tirinhas
class Quadro {
	public $width = 1080;
	public $height = 1080;
	//public $txtLinesWidth = 670;
	//public $lineHeight = 50;
	public $font = "./Comic Sans MS.ttf";
	public $fontSize = 30;
	public $imgSource;
	//public $txtQuadro;
	public $txtBoxWidth;	#centro do balao x
	public $txtBoxHeight;	#centro do balao y
	public $image; 		#binary
	public $imageData;	#base64
	
	#pega parametros, imagens, texto e monta um quadrinho com texto
	function __construct($imgSource, $txtBoxWidth, $txtBoxHeight, $txtQuadro) {
		$this->imgSource = $imgSource;
		$this->txtBoxWidth = $txtBoxWidth;
		$this->txtBoxHeight = $txtBoxHeight;
		//$this->txtQuadro = $txtQuadro;
		
		$this->writeToImage($txtQuadro);
	}
	
	#escreve textos nas imagens
	function writeToImage($text) {;
		$image = ImageCreateFromPNG($this->imgSource);
		$color = imagecolorallocate($image, 0, 0, 0);
		$textAdjusted = wordwrap($text, 30, "\n");		
		$textBox = imagettfbbox($this->fontSize, 0, $this->font, $textAdjusted);
		$textWidth = $textBox[2]-$textBox[0];
		$textHeight = $textBox[1]-$textBox[7];
		$x = $this->txtBoxWidth - $textWidth / 2;
		$y = ($this->txtBoxHeight - $textHeight / 2)  + $this->fontSize; #pois a ref. eh abaixo da primeira linha de texto
				
		imagettftext($image, $this->fontSize, 0, $x, $y, $color, $this->font, $textAdjusted);
		
		$this->image = $image;
		$this->imageData = Image::convertToBase64($image);
	}
}



#constroi tirinhas a partir de quadrinhos
class Tirinha {
	public $width = 1080;
	public $height = 1080;
	public $image; 		#binary
	public $imageData;	#base64
	
	#produz a tirinha completa
	function __construct($image1, $image2, $image3) {
		$image = imagecreatetruecolor ($this->width, $this->height);
		$bgColor = imagecolorallocate($image, 255, 255, 255);
		imagefill($image, 0, 0, $bgColor);
		imagecopyresampled ($image, $image1, 0, 0, 0, 0, $this->width / 2, $this->height / 2, $this->width, $this->height);
		imagecopyresampled ($image, $image2, $this->width / 2, 0, 0, 0, $this->width / 2, $this->height / 2, $this->width, $this->height);
		imagecopyresampled ($image, $image3, $this->width / 4, $this->height / 2, 0, 0, $this->width /2 , $this->height /2 , $this->width, $this->height);
		$this->image = $image;
		$this->imageData = Image::convertToBase64($image);
	}
}



#geracao de legandas
class Legenda {
	public $legendaMontada;
	public $siga = "Siga Frank –> @frankabaleiafranca";
	public $hashtagDefault = "#frankabaleiafranca #falomesmo #humoracido #tirinhas ";
	public $hashtags = array(
		"#quadrinhos #tirinhasinteligentes #humorbrasil",
		"#tirinhasengraçadas #quadrinhosgram #sincerona",
		"#humorinteligente #tirinhasengraçadas #baleiafranca",
		"#humorbrasil #ficaemcasa #tirinha"
	);
	
	function __construct($txtQuadro1 = "", $txtQuadro2 = "", $txtQuadro3 = "") {
		console_log ("construct legenda");
		$legenda = $txtQuadro1;
		$legenda .= "\n\n";
		$legenda .= $txtQuadro2;
		$legenda .= "\n\n";
		$legenda .= $this->siga;
		$legenda .= "\n\n";
		$legenda .= $this->hashtagDefault;
		$legenda .= $this->hashtags[array_rand($this->hashtags, 1)];
		$this->legendaMontada = $legenda;
				
		return $legenda;
	}
}



#MAIN
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$txtQuadro1 = $_POST['txtQuadro1'];
	$txtQuadro2 = $_POST['txtQuadro2'];
	$txtQuadro3 = $_POST['txtQuadro3'];
	
	#criacao objetos quadros - os null sao pq php nao suporta overloading de metodo
	$quadro1 = new Quadro('frank1.png', 451, 233, $txtQuadro1);
	$quadro2 = new Quadro('frank2.png', 428, 222, $txtQuadro2);
	$quadro3 = new Quadro('frank3.png', null, null, null);
	
	#gerar a tirinha com os quadros acima
	$tirinha = new Tirinha($quadro1->image, $quadro2->image, $quadro3->image);
	
	#criacao das sources a partir dos base64 gerados	
	$image1Source = "data:image/png;base64," . $quadro1->imageData;
	$image2Source = "data:image/png;base64," . $quadro2->imageData;
	$image3Source = "data:image/png;base64," . $quadro3->imageData;
	$tirinhaSource = "data:image/png;base64," . $tirinha->imageData;
	
	#preenche a legenda
	$legenda = new Legenda($txtQuadro1, $txtQuadro2, $txtQuadro3);
	$legendaMontada = $legenda->legendaMontada;
} else {
	#se nao ha POST - primeiro acesso ao site
	$image1Source = "frank1.png";
	$image2Source = "frank2.png";
	$image3Source = "frank3.png";
	$tirinhaSource = "frank4.png";
}

?>

<!doctype html>
<html lang="pt-br">
<html>
	<header>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous">
		<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
		<link rel="icon" href="/favicon.ico" type="image/x-icon">
		<title>Frankzator, o gerador de Frank, a Baleia Franca</title>
	</header>
	<body>
		<div class="container mt-3 mt-4">
			<h1 class="pt-3">Frankzator 1.0</h1>
			<div class="help-block">
				<small class="text-muted">
				Preencha os textos dos quadrinhos 1 e 2 e aperte o botão para trazer o Frank à vida! Depois, salve as quatro imagens individuais e a tirinha inteira clicando nos botões 1, 2, 3 e Tira. (Alguns celulares são meio merdas e será necessário segurar o dedão sobre a imagem para poder salvá-la na galeria.) Aí é só clicar no botão copiar legenda. Pronto. Você já tem as 4 imagens e a legenda com as hashtags para postar ou agendar no <a href="https://www.instagram.com/frankabaleiafranca/" target="_blank">@frankabaleiafranca</a>.
				</small>
			</div>
		</div>
		<div class="container mt-3 p-4 bg-light border rounded">
			<h3 class="pb-1">Diálogos</h3><br>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
			    <div class="mb-3">
			    	<label for="txtQuadro1" class="form-label">Texto do Quadrinho 1</label>
			    	<textarea class="form-control" id="txtQuadro1" name="txtQuadro1" rows="3" required><?php echo $txtQuadro1 ?></textarea>
			    	<div class="form-text">Fala do boca mole.</div>
			    </div>
				
			    <div class="mb-3">
			    	<label for="txtQuadro2" class="form-label">Texto do Quadrinho 2</label>
			    	<textarea class="form-control" id="txtQuadro2" name="txtQuadro2" rows="3" required><?php echo $txtQuadro2 ?></textarea>
			    	<div class="form-text">Resposta atravesada da baleia.</div>
				</div>
				<div class="btn-group mr-2 mb-2" role="button" aria-label="Botão Gerador">
					<button type="submit" class="btn btn-dark">Trazer Frank à vida! 🐳</button>
				</div>
			</form>
			</div>
			
			<div class="container mt-5 p-4 bg-light border rounded">
				<h3 class="pb-1">Legenda</h3><br>
				<form>
				    <div class="mb-3">
				    	<label for="txtLegenda" class="form-label">Algum comentário no início da legenda (opcional)</label>
				    	<textarea class="form-control" id="txtLegenda" rows="3" required><?php echo $legendaMontada ?></textarea>
				    	<div class="form-text">Adicionar algum comentário no início da legenda. Serão adicionadas informações e hashtags quando gerar os quadrinhos.</div>
				    </div>
					<div class="btn-group mr-2 mb-2" role="button" aria-label="Copiar Legenda">
						<button type="button" class="btn btn-dark" onclick="copyToClipboard('txtLegenda');">Copiar Legenda</button>
					</div>
				</form>
			</div>
			
			<div class="container mt-5 p-4 bg-light border rounded">
			<div>
				<h3 class="pb-1">Quadrinhos isolados</h3><br>
				 <div>
					<div class="btn-toolbar" role="toolbar" aria-label="Toolbar">
						<div class="btn-group btn-group-lg mb-3" role="group" aria-label="Botões Download">
							<a id="btnFrank1" href="<?php echo $image1Source ?>" download="frank1.png">
						  		<button type="button" class="btn btn-dark">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud-arrow-down-fill" viewBox="0 0 16 16">
										<path d="M8 2a5.53 5.53 0 0 0-3.594 1.342c-.766.66-1.321 1.52-1.464 2.383C1.266 6.095 0 7.555 0 9.318 0 11.366 1.708 13 3.781 13h8.906C14.502 13 16 11.57 16 9.773c0-1.636-1.242-2.969-2.834-3.194C12.923 3.999 10.69 2 8 2zm2.354 6.854l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 1 1 .708-.708L7.5 9.293V5.5a.5.5 0 0 1 1 0v3.793l1.146-1.147a.5.5 0 0 1 .708.708z"/>
								  	</svg> 1
								</button>
							</a>
							&nbsp;
							<a id="btnFrank2" href="<?php echo $image2Source ?>" download="frank2.png">
								<button type="button" class="btn btn-dark"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud-arrow-down-fill" viewBox="0 0 16 16">
									<path d="M8 2a5.53 5.53 0 0 0-3.594 1.342c-.766.66-1.321 1.52-1.464 2.383C1.266 6.095 0 7.555 0 9.318 0 11.366 1.708 13 3.781 13h8.906C14.502 13 16 11.57 16 9.773c0-1.636-1.242-2.969-2.834-3.194C12.923 3.999 10.69 2 8 2zm2.354 6.854l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 1 1 .708-.708L7.5 9.293V5.5a.5.5 0 0 1 1 0v3.793l1.146-1.147a.5.5 0 0 1 .708.708z"/>
									</svg> 2
								</button>
							</a>
							&nbsp;
							<a id="btnFrank3" href="<?php echo $image3Source ?>" download="frank3.png">
								<button type="button" class="btn btn-dark"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud-arrow-down-fill" viewBox="0 0 16 16">
									<path d="M8 2a5.53 5.53 0 0 0-3.594 1.342c-.766.66-1.321 1.52-1.464 2.383C1.266 6.095 0 7.555 0 9.318 0 11.366 1.708 13 3.781 13h8.906C14.502 13 16 11.57 16 9.773c0-1.636-1.242-2.969-2.834-3.194C12.923 3.999 10.69 2 8 2zm2.354 6.854l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 1 1 .708-.708L7.5 9.293V5.5a.5.5 0 0 1 1 0v3.793l1.146-1.147a.5.5 0 0 1 .708.708z"/>
									</svg> 3
								</button>
							</a>
							&nbsp;
							<a id="btnFrank4" href="<?php echo $tirinhaSource ?>" download="frank4.png">
								<button type="button" class="btn btn-dark"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud-arrow-down-fill" viewBox="0 0 16 16">
				  <path d="M8 2a5.53 5.53 0 0 0-3.594 1.342c-.766.66-1.321 1.52-1.464 2.383C1.266 6.095 0 7.555 0 9.318 0 11.366 1.708 13 3.781 13h8.906C14.502 13 16 11.57 16 9.773c0-1.636-1.242-2.969-2.834-3.194C12.923 3.999 10.69 2 8 2zm2.354 6.854l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 1 1 .708-.708L7.5 9.293V5.5a.5.5 0 0 1 1 0v3.793l1.146-1.147a.5.5 0 0 1 .708.708z"/>
				</svg> Tira</button>
											</a>
				 					  	</div>	
				 					</div>
								</div>
				<div class="row">
					<div class="col-sm">
						
						<img id="frank1" src="<?php echo $image1Source ?>" class="img-thumbnail" alt="frank1.png">
						<!-- img id="frank1" src="frank1.png" class="img-thumbnail" alt="frank1.png" -->
					</div>
					<div class="col-sm">
						<img id="frank2" src="<?php echo $image2Source ?>" class="img-thumbnail" alt="frank2.png">
					</div>
					<div class="col-sm">
						<img id="frank3" src="<?php echo $image3Source ?>" class="img-thumbnail" alt="frank3.png">
					</div>
				</div>	
			</div>
			<div>
				<h3 class="pt-5 pb-1">Tirinha completa</h3><br>
				<img id="frank4" src="<?php echo $tirinhaSource ?>" class="img-fluid"  alt="frank4.png">
			</div>
		</div>
		<div class="container p-4 bg-white">
			<div class="help-block">
				<h4 class="pt-5 text-muted">Como postar no Instagrão</h4>
				<small class="text-muted">
				Se você foi condecorado com o título de editor de Frank, poderá postar e agendar posts no Instagram e na página do Facebook do Frank! Wow!<br>
				Se estiver no computador, acesse as <a href="http://facebook.com/pages/" target="_blank">páginas do Facebook</a> e clique em Convites. Se acessar do celular, abra o app do Facebook, vá às páginas (bandeirinha) e tá lá o botão Convites em algum lugar com o convite para você ser Editor do Frank. Aceite, obviamente.
				<h6 class="pt-5 text-muted">Como postar de um computador:</h6>
				1) É só ir ao <a href="https://business.facebook.com/creatorstudio?tab=instagram_content_posts&collection_id=free_form_collection" target="_blank"> Creators Studio</a> para começar a postar. Lá é bem fácil. Até uma anta capivara gorda consegue. Bem em cima deve ter uma barra com os ícones do Facebook e do Instagram. O Instagram deve estar selecionado porque eu fiz uma mágica nesse link. Se a mágina não funcionou, selecione o ícone do Instagram.<br>
				2) Logo ao lado deve ter um botão Criar Publicação. Adivinha, só! Clica nele.<br>
				3) Vai abrir uma aba perguntando em qual perfil postar. Selecione o do Frank. Aí é só colar a leganda no campo gigante para a legenda e as 4 imagens logo abaixo.<br>
				Às vezes o bagulho é meio burro e coloca os quadrinhos fora de ordem... Em cada imagem tem um numerozinho que indica sua ordem. Troque o número e aperte Enter que ele reordena. Você consegue. São só 4 possibilidades: 1, 2, 3 e adivinha! 4.<br>
				4) Pronto. Marque Publicar no Facebook para também ir para a página do Frank no Facebook e clique no botãozão azul Publicar para a mágica acontecer.<br>
				5) Caso queira agendar, é só clicar na setinha ao lado do botão e escolher Programar. Atenção aqui, tem que fazer a mesma coisa no botãozinho cinza que aparece ao lado de Publicar no Facebook. O agendamento dos dois é independente, então tem que fazer nos dois. Falei que o bagulho era meio burro, né?
				<h6 class="pt-5 text-muted">Como postar de um telefone:</h6>
				1) Baixe o app Facebook Business Suite e faça login.<br>
				2) No canto superior esquerdo tem a sua cara. Escolha a do Frank. É SUPERIOR esquerdo e não inferior.<br>
				3) Use o botão azul para criar uma publicação, agendar etc. Vá avançando. A última página de configuração da postagem contem o agendamento bem acima.
				<h6 class="pt-5 text-muted">Como postar usando a sua bunda:</h6>
				Infelizmente, essa função ainda não está disponível nesta versão.
				<h6 class="pt-5 text-muted">Bugs conhecidos e outras merdas que possam acontecer:</h6>
				Alguns browsers no celular complicam com o botão de download. Contorne o problema: segura o dedão em cima e manda salvar.
				</small>
			</div>
		</div>
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js" integrity="sha384-JEW9xMcG8R+pH31jmWH6WWP0WintQrMb4s7ZOdauHnUtxwoG2vI5DkLtS3qm9Ekf" crossorigin="anonymous"></script>
		<script>
			function copyToClipboard(element) {
			  var copyText = document.getElementById(element);
			  copyText.select();
			  copyText.setSelectionRange(0, 99999); /* For mobile devices */
			  document.execCommand("copy");
			  alert("Legenda copiada! Cole no instagram");
			}
		</script>
	</body>
</html>