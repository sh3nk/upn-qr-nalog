<?php
	$order = wc_get_order($orderId);
	$orderData = $order->get_data();
	$billing = $orderData['billing'];
	
	// Data for form
	$data = array(
		'placnik' => array(
			'ime' => $billing['first_name'] . ' ' . $billing['last_name'],
			'ulica' => ($billing['address_2']) ? $billing['address_1'] . ', ' . $billing['address_2'] : $billing['address_1'],
			'kraj' => $billing['postcode'] . ' ' . $billing['city']
		),
		'znesek' => '***' . number_format($orderData['total'], 2, ',', '.'),
		'koda_namena' => 'GDSV',
		'namen_placila' => 'Plačilo računa št. ' . $order->get_id(),
		'rok_placila' => date('d.m.Y', time() + (7*24*3600)),
		'iban_prejemnika' => get_option('uq_iban'),
		'referenca_prejemnika' => array(
			'model' => 'SI00',
			'sklic' => $order->get_id()
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
	<h2>UPN QR</h2>
 
	<div id="uq-background">
		<!--	potrdilo	-->
		<p class="uq-data uq-data-potrdilo" style="top: 26px;">
			<?php echo $data['placnik']['ime']; ?><br>
			<?php echo $data['placnik']['ulica']; ?><br>
			<?php echo $data['placnik']['kraj']; ?>
		</p>
		<p class="uq-data uq-data-potrdilo" style="top: 87px;">
			<?php echo $data['namen_placila'] . ','; ?><br>
			<?php echo $data['rok_placila']; ?>
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
		<p class="uq-data uq-data-poloznica" style="top: 84px; left: 394px;">
			<?php echo $data['placnik']['ime']; ?>
		</p>
		<p class="uq-data uq-data-poloznica" style="top: 102px; left: 394px;">
			<?php echo $data['placnik']['ulica']; ?>
		</p>
		<p class="uq-data uq-data-poloznica" style="top: 120px; left: 394px;">
			<?php echo $data['placnik']['kraj']; ?>
		</p>
		
		<p class="uq-data uq-data-poloznica" style="top: 151px; left: 421px;">
			<?php echo $data['znesek']; ?>
		</p>
		<p class="uq-data uq-data-poloznica" style="top: 183px; left: 237px;">
			<?php echo $data['koda_namena']; ?>
		</p>
		<p class="uq-data uq-data-poloznica" style="top: 183px; left: 300px;">
			<?php echo $data['namen_placila']; ?>
		</p>
		<p class="uq-data uq-data-poloznica" style="top: 183px; left: 650px;">
			<?php echo $data['rok_placila']; ?>
		</p>
		
		<!--	prejemnik	-->
		<p class="uq-data uq-data-poloznica" style="top: 216px; left: 237px;">
			<?php echo $data['iban_prejemnika']; ?>
		</p>
		<p class="uq-data uq-data-poloznica" style="top: 245px; left: 237px;">
			<?php echo $data['referenca_prejemnika']['model']; ?>
		</p>
		<p class="uq-data uq-data-poloznica" style="top: 245px; left: 300px;">
			<?php echo $data['referenca_prejemnika']['sklic']; ?>
		</p>
		<p class="uq-data uq-data-poloznica" style="top: 274px; left: 237px;">
			<?php echo $data['prejemnik']['ime']; ?>
		</p>
		<p class="uq-data uq-data-poloznica" style="top: 292px; left: 237px;">
			<?php echo $data['prejemnik']['ulica']; ?>
		</p>
		<p class="uq-data uq-data-poloznica" style="top: 310px; left: 237px;">
			<?php echo $data['prejemnik']['kraj']; ?>
		</p>

        <!--    QR    -->
        <canvas id="uq-qrcode"></canvas>
		
	</div>
    
    <div id="uq-data" data-value='<?php echo $qrString; ?>'></div>
</div>
