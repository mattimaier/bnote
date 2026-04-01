<?php

if(!function_exists("bnote_render_legacy_login_nextgen_banner")) {
	function bnote_render_legacy_login_nextgen_banner($rawLang = "en") {
		$helperDir = __DIR__;
		$nextGenRoot = dirname($helperDir);
		$indexFile = $nextGenRoot . DIRECTORY_SEPARATOR . "index.html";
		$apiIndexFile = $nextGenRoot . DIRECTORY_SEPARATOR . "api" . DIRECTORY_SEPARATOR . "index.php";
		$apiIndexSameDir = $helperDir . DIRECTORY_SEPARATOR . "index.php";
		if(!is_file($indexFile) && !is_file($apiIndexFile)) {
			$apiSameDirExists = is_file($apiIndexSameDir);
			if($apiSameDirExists) {
				// Fallback for deployments where only API files are present at this point.
			}
			else {
			return;
			}
		}

		$copy = array(
			"de" => array(
				"title" => "BNote Next Generation (beta)",
				"subtitle" => "Alles im Takt: modern, schnell, übersichtlich.",
				"cta" => "Jetzt ausprobieren",
			),
			"en" => array(
				"title" => "BNote Next Generation (beta)",
				"subtitle" => "Everything in sync: modern, fast, clear.",
				"cta" => "Try now",
			),
			"es" => array(
				"title" => "BNote Next Generation (beta)",
				"subtitle" => "Todo en sincronía: moderno, rápido y claro.",
				"cta" => "Probar ahora",
			),
			"fr" => array(
				"title" => "BNote Next Generation (beta)",
				"subtitle" => "Tout en rythme : moderne, rapide et clair.",
				"cta" => "Essayer maintenant",
			),
		);

		$lang = "en";
		$currentLang = strtolower(trim((string)$rawLang));
		if(isset($copy[$currentLang])) {
			$lang = $currentLang;
		}
		else {
			$shortLang = substr($currentLang, 0, 2);
			if(isset($copy[$shortLang])) {
				$lang = $shortLang;
			}
		}

		$title = htmlspecialchars($copy[$lang]["title"], ENT_QUOTES, "UTF-8");
		$subtitle = htmlspecialchars($copy[$lang]["subtitle"], ENT_QUOTES, "UTF-8");
		$cta = htmlspecialchars($copy[$lang]["cta"], ENT_QUOTES, "UTF-8");

		$scriptName = isset($_SERVER["SCRIPT_NAME"]) ? (string)$_SERVER["SCRIPT_NAME"] : "";
		$scriptDir = str_replace("\\", "/", dirname($scriptName));
		$parentPrefix = str_replace("\\", "/", dirname($scriptDir));
		if($parentPrefix === ".") {
			$parentPrefix = "";
		}
		$parentPrefix = rtrim($parentPrefix, "/");
		$nextGenHref = ($parentPrefix !== "" ? $parentPrefix : "") . "/bnote-next-generation/";
		$nextGenHref = htmlspecialchars($nextGenHref, ENT_QUOTES, "UTF-8");
		?>
		<style>
			.nextgen-login-banner {
				display: block;
				margin: 0.9rem auto 1.8rem;
				width: calc(100% - 1rem);
				max-width: 448px;
				border-radius: 0.75rem;
				overflow: hidden;
				background: transparent;
				border: 1px solid #dbe3ea;
				box-shadow: 0 10px 18px rgba(36, 62, 86, 0.12);
				pointer-events: none;
				font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			}
			.nextgen-login-banner-tape-line {
				background: repeating-linear-gradient(
					45deg,
					#000000,
					#000000 8.5px,
					#ffaa1a 8.5px,
					#ffaa1a 17px
				);
				background-size: 24.04px 24.04px;
				height: 0.46rem;
				animation: nextgen-tape-move 2.7s linear infinite;
			}
			.nextgen-login-banner-inner {
				background: #eef2f6;
				padding: 1.05rem 1.1rem 1.2rem;
				text-align: center;
			}
			.nextgen-login-banner-logo-box {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				width: 6rem;
				height: 6rem;
				margin: 0 auto 0.7rem;
			}
			.nextgen-login-banner-title {
				color: #243e56;
				font-weight: 800;
				letter-spacing: 0.01em;
				font-size: 1.2rem;
				line-height: 1.12;
				text-align: center;
				margin: 0 0 0.45rem;
			}
			.nextgen-login-banner-subtitle {
				color: #4f5e6f;
				font-size: 0.95rem;
				line-height: 1.35;
				max-width: 26rem;
				margin: 0 auto 0.95rem;
			}
			.nextgen-login-banner-cta {
				margin: 0 auto;
				width: auto;
				display: inline-block;
				pointer-events: auto;
			}
			.nextgen-login-banner-cta.btn.btn-primary {
				background: linear-gradient(90deg, #4a9bff, #5aa8ff);
				border-color: #4a9bff;
				font-size: 1rem;
				font-weight: 700;
				padding: 0.68rem 1.25rem;
				min-width: 17rem;
				border-radius: 0.55rem;
			}
			.nextgen-login-banner-cta.btn.btn-primary:hover,
			.nextgen-login-banner-cta.btn.btn-primary:focus {
				background: linear-gradient(90deg, #3f8fea, #4f9cef);
				border-color: #3f8fea;
				text-decoration: none;
			}
			.nextgen-login-banner-cta:focus-visible {
				outline: 2px solid #243e56;
				outline-offset: 2px;
			}
			@keyframes nextgen-tape-move {
				0% { background-position: 0 0; }
				100% { background-position: 24.04px 24.04px; }
			}
			@media (max-width: 576px) {
				.nextgen-login-banner {
					width: calc(100% - 0.6rem);
				}
				.nextgen-login-banner-inner {
					padding: 0.85rem 0.8rem 0.95rem;
				}
				.nextgen-login-banner-logo-box {
					width: 5rem;
					height: 5rem;
					margin-bottom: 0.62rem;
				}
				.nextgen-login-banner-subtitle {
					font-size: 0.88rem;
					margin-bottom: 0.8rem;
				}
				.nextgen-login-banner-cta {
					min-width: 0;
				}
				.nextgen-login-banner-cta.btn.btn-primary {
					min-width: 12.5rem;
					padding: 0.6rem 1rem;
				}
			}
		</style>
		<div id="nextgen-login-banner" class="nextgen-login-banner" role="region" aria-label="<?php echo $title; ?>">
			<div class="nextgen-login-banner-tape-line"></div>
			<div class="nextgen-login-banner-inner">
				<div class="nextgen-login-banner-logo-box" aria-hidden="true">
					<svg viewBox="0 0 512 512" class="nextgen-login-banner-logo" role="img" aria-label="BNote">
						<defs>
							<linearGradient id="bnoteLogoBgLegacyBanner" x1="0" y1="0" x2="1" y2="1">
								<stop offset="0" stop-color="#c2e0ff"></stop>
								<stop offset="1" stop-color="#ebf5ff"></stop>
							</linearGradient>
						</defs>
						<rect x="0" y="0" width="512" height="512" rx="77" ry="77" fill="url(#bnoteLogoBgLegacyBanner)"></rect>
						<rect x="7" y="7" width="498" height="498" rx="70" ry="70" fill="none" stroke="#d6ebff" stroke-width="14"></rect>
						<g transform="translate(87,87) scale(0.4225)">
							<path fill="#3399ff" fill-rule="evenodd" d="m517.5 59c19.8-8.4 42.6 0.8 51 20.6l198.7 468c8.3 19.8-0.9 42.6-20.7 51l-463.7 196.8c-19.8 8.4-42.6-0.8-51-20.5l-198.7-468.1c-8.4-19.7 0.8-42.6 20.6-51l12.7-5.3c19.8-8.4 42.6 0.8 51 20.5l6.4 14.2c6.3 14.9 23.5 21.9 38.5 15.6 14.9-6.4 1.6-0.7 16.5-7.1 15-6.3 22-23.5 15.6-38.5l-6-14.3c-8.4-19.7 0.8-42.6 20.6-51l156.4-66.3c19.7-8.4 42.5 0.8 50.9 20.6l6.4 14.1c6.3 15 23.6 21.9 38.5 15.6 15-6.4 1.6-0.7 16.6-7.1 14.9-6.3 21.9-23.5 15.6-38.5l-6.1-14.3c-8.3-19.7 0.9-42.5 20.6-51zm-375.8 382.3l115.6 274c8.4 19.8 31.2 29 51 20.6l375.9-159.5c19.8-8.4 29-31.3 20.6-51l-116.2-273.8c-8.4-19.7-31.2-28.9-51-20.6l-375.3 159.4c-19.8 8.4-29 31.2-20.6 50.9zm334 64.4c4.3-5.2 9.1-9.6 14.1-13.4l-43-119.1-92.6 55.5 57.8 160.5q1.2 3.1 1.6 6.2c4 17-2.1 39.2-17.6 57.6-22.3 26.4-55.4 35-74 19.3-18.6-15.6-15.6-49.7 6.6-76.1 4.6-5.4 9.6-10 14.8-13.9l-56.4-156.4c-5-13.7 0.4-28.7 12.1-36.4q1.4-1.2 3.1-2.2l143.2-85.7q0.9-0.6 1.9-1.1 2.5-1.5 5.4-2.5c16.2-5.9 34 2.5 39.9 18.6l64.5 178.9c9.6 17.5 4.5 44.9-14.1 67-22.3 26.3-55.4 35-74 19.3-18.5-15.7-15.6-49.8 6.7-76.1zm-375.4-373.4c15.8-6.7 34.1 0.7 40.8 16.5l42.4 99.9c6.7 15.8-0.7 34.1-16.5 40.8-15.8 6.7-34.1-0.7-40.8-16.5l-42.4-99.9c-6.7-15.8 0.7-34.1 16.5-40.8zm298.7-126.7c15.8-6.8 34 0.6 40.8 16.4l42.4 99.9c6.7 15.8-0.7 34.1-16.5 40.8-15.9 6.7-34.1-0.7-40.8-16.5l-42.4-99.9c-6.7-15.8 0.6-34 16.5-40.7zm-345.3 250.3l463.8-196.9"></path>
						</g>
					</svg>
				</div>
				<div class="nextgen-login-banner-title"><?php echo $title; ?></div>
				<div class="nextgen-login-banner-subtitle"><?php echo $subtitle; ?></div>
				<div style="text-align:center;">
					<a href="<?php echo $nextGenHref; ?>" class="btn btn-primary btn-lg nextgen-login-banner-cta"><?php echo $cta; ?></a>
				</div>
			</div>
		</div>
		<script>
			(function() {
				var banner = document.getElementById('nextgen-login-banner');
				var optionsbar = document.getElementById('optionsbar');
				if(!banner || !optionsbar || !optionsbar.parentNode) return;
				optionsbar.parentNode.insertBefore(banner, optionsbar);
			})();
		</script>
		<?php
	}
}
