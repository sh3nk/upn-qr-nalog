<?php
	$order = wc_get_order($orderId);
	$orderData = $order->get_data();
	$billing = $orderData['billing'];
	
	// Only show UPNQR for BACS payment method
	if ($orderData['payment_method'] != 'bacs') {
	    return;
    }
	
	// Data for form
	$data = array(
		'placnik' => array(
			'ime' => $billing['first_name'] . ' ' . $billing['last_name'],
			'ulica' => ($billing['address_2']) ? $billing['address_1'] . ', ' . $billing['address_2'] : $billing['address_1'],
			'kraj' => $billing['postcode'] . ' ' . $billing['city']
		),
		'znesek' => '***' . number_format($orderData['total'], 2, ',', '.'),
		'koda_namena' => strtoupper(substr(get_option('uq_koda'), 0, 4)),
		'namen_placila' => str_replace('%id%', $order->get_id(), get_option('uq_namen')),
		'rok_placila' => '',//date('d.m.Y', time() + (7*24*3600)),
		'iban_prejemnika' => get_option('uq_iban'),
		'referenca_prejemnika' => array(
			'model' => substr(get_option('uq_model'), 0, 4),
			'sklic' => str_replace('%id%', $order->get_id(), get_option('uq_sklic'))
		),
		'prejemnik' => array(
			'ime' => get_option('uq_ime'),
			'ulica' => get_option('uq_ulica'),
			'kraj' => get_option('uq_kraj')
		)
	);
	
	if (!$data['iban_prejemnika'] || !$data['prejemnik']['ime'] || !$data['prejemnik']['ulica'] || !$data['prejemnik']['kraj']) {
		return;
	}
	
	// Field character limit checks
    if (mb_strlen($data['placnik']['ime']) > 33) {
	    $data['placnik']['ime'] = mb_substr($data['placnik']['ime'], 0, 33);
    }
    if (mb_strlen($data['placnik']['ulica']) > 33) {
        $data['placnik']['ulica'] = mb_substr($data['placnik']['ulica'], 0, 33);
    }
    if (mb_strlen($data['placnik']['kraj']) > 33) {
        $data['placnik']['kraj'] = mb_substr($data['placnik']['kraj'], 0, 33);
    }
    if ($orderData['total'] > 1000000000 - 1) {
	    $data['znesek'] = 1000000000 - 1;
    }
    if (mb_strlen($data['namen_placila']) > 42) {
        $data['namen_placila'] = mb_substr($data['namen_placila'], 0, 42);
    }
    if (mb_strlen($data['referenca_prejemnika']['sklic']) > 22) {
        $data['referenca_prejemnika']['sklic'] = mb_substr($data['referenca_prejemnika']['sklic'], 0, 22);
    }
    if (mb_strlen($data['prejemnik']['ime']) > 33) {
        $data['prejemnik']['ime'] = mb_substr($data['prejemnik']['ime'], 0, 33);
    }
    if (mb_strlen($data['prejemnik']['ulica']) > 33) {
        $data['prejemnik']['ulica'] = mb_substr($data['prejemnik']['ulica'], 0, 33);
    }
    if (mb_strlen($data['prejemnik']['kraj']) > 33) {
        $data['prejemnik']['kraj'] = mb_substr($data['prejemnik']['kraj'], 0, 33);
    }

    // Data for QR code
	$qrData = array(
		'vodilni_slog' => "UPNQR\n",
		'iban_placnika' => "\n",
		'polog' => "\n",
		'dvig' => "\n",
		'referenca_placnika' => "\n",
		'ime_placnika' => $data['placnik']['ime'] . "\n",
		'ulica_placnika' => $data['placnik']['ulica'] . "\n",
		'kraj_placnika' => $data['placnik']['kraj'] . "\n",
		'znesek' => sprintf('%011d', $orderData['total'] * 100) . "\n",
		'datum_placila' => "\n",
		'nujno' => "\n",
		'koda_namena' => $data['koda_namena'] . "\n",
		'namen_placila' => $data['namen_placila'] . "\n",
		'rok_placila' => $data['rok_placila'] . "\n",
		'iban_prejemnika' => str_replace(' ', '', get_option('uq_iban')) . "\n",
		'referenca_prejemnika' => 'SI00' . $order->get_id() . "\n",
		'ime_prejemnika' => $data['prejemnik']['ime'] . "\n",
		'ulica_prejemnika' => $data['prejemnik']['ulica'] . "\n",
		'kraj_prejemnika' => $data['prejemnik']['kraj'] . "\n",
	);
	
	$checksum = 0;
	foreach ($qrData as $field) {
		$checksum += mb_strlen($field);
	}
	$qrData['kontrolna_vsota'] = sprintf('%03d', $checksum) . "\n";
	
	// Combine data to single string
	$qrString = implode('', $qrData);
	
	// Convert data to ISO 8859-2 charset
    $qrString = iconv('UTF-8', 'ISO-8859-2', $qrString);
    
    // Get byte array of all characters
    $qrString = unpack('C*', $qrString);
	
    // Convert to json to use in generate-qr.js
	$qrString = json_encode($qrString);
?>

<div id="uq-nalog">
	<h2>UPN QR za mobilno banko</h2>

    <canvas id="uq-qrcode-big"></canvas>
    
    <h2>UPN Nalog</h2>
    <p>Podatke lahko prepišete na UPN nalog.</p>
 
	<div id="uq-background">
    <img id="uq-bg-image" src="<?php echo UQ__PLUGIN_URL; ?>public/upn.png" alt="">
        
		<!--	potrdilo	-->
		<p class="uq-data uq-data-potrdilo" style="top: 26px;">
			<?php echo $data['placnik']['ime']; ?><br>
			<?php echo $data['placnik']['ulica']; ?><br>
			<?php echo $data['placnik']['kraj']; ?>
		</p>
		<p class="uq-data uq-data-potrdilo" style="top: 87px;">
      <?php echo $data['namen_placila']; ?>
      <?php if ($data['rok_placila']): ?>
        ,<br>
        <?php echo $data['rok_placila']; ?>
      <?php endif; ?>
		</p>
		<p class="uq-data uq-data-potrdilo" style="top: 129px; left: 64px;">
			<?php echo $data['znesek']; ?>
		</p>
		<p class="uq-data uq-data-potrdilo" style="top: 160px;">
			<?php echo $data['iban_prejemnika']; ?><br>
			<br>
			<?php echo $data['referenca_prejemnika']['model'] . ' ' . $data['referenca_prejemnika']['sklic']; ?>
		</p>
		<p class="uq-data uq-data-potrdilo" style="top: 219px;">
			<?php echo $data['prejemnik']['ime']; ?><br>
			<?php echo $data['prejemnik']['ulica']; ?><br>
			<?php echo $data['prejemnik']['kraj']; ?>
		</p>
		
		<!--	placnik	    -->
		<p class="uq-data uq-data-poloznica" style="top: 86px; left: 392px;">
			<?php echo $data['placnik']['ime']; ?>
		</p>
		<p class="uq-data uq-data-poloznica" style="top: 104px; left: 392px;">
			<?php echo $data['placnik']['ulica']; ?>
		</p>
		<p class="uq-data uq-data-poloznica" style="top: 122px; left: 392px;">
			<?php echo $data['placnik']['kraj']; ?>
		</p>
		
		<p class="uq-data uq-data-poloznica" style="top: 151px; left: 421px;">
			<?php echo $data['znesek']; ?>
		</p>
		<p class="uq-data uq-data-poloznica" style="top: 183px; left: 235px;">
			<?php echo $data['koda_namena']; ?>
		</p>
		<p class="uq-data uq-data-poloznica" style="top: 183px; left: 298px;">
			<?php echo $data['namen_placila']; ?>
		</p>
		<p class="uq-data uq-data-poloznica" style="top: 183px; left: 648px;">
			<?php echo $data['rok_placila']; ?>
		</p>
		
		<!--	prejemnik	-->
		<p class="uq-data uq-data-poloznica" style="top: 216px; left: 235px;">
			<?php echo $data['iban_prejemnika']; ?>
		</p>
		<p class="uq-data uq-data-poloznica" style="top: 245px; left: 235px;">
			<?php echo $data['referenca_prejemnika']['model']; ?>
		</p>
		<p class="uq-data uq-data-poloznica" style="top: 245px; left: 298px;">
			<?php echo $data['referenca_prejemnika']['sklic']; ?>
		</p>
		<p class="uq-data uq-data-poloznica" style="top: 276px; left: 235px;">
			<?php echo $data['prejemnik']['ime']; ?>
		</p>
		<p class="uq-data uq-data-poloznica" style="top: 294px; left: 235px;">
			<?php echo $data['prejemnik']['ulica']; ?>
		</p>
		<p class="uq-data uq-data-poloznica" style="top: 312px; left: 235px;">
			<?php echo $data['prejemnik']['kraj']; ?>
		</p>

        <!--    QR    -->
        <canvas id="uq-qrcode"></canvas>
		
	</div>
    
    <div id="uq-data" data-value='<?php echo $qrString; ?>'></div>
</div>
