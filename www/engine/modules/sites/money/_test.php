<?php
require_once(_MODULES_.'/index.php');

class _test extends parentTemplate
{

    function _act_()    {
        $tpl = get_template('', $this->module, 'body1');

        add_log('тест тест', "", 3);
        pr('mewew');
//        add_log('Мой вложенный log', '/_common.log');
//        add_log('Мой вложенный log', 'sub1/_common.log');
//        add_log('Мой вложенный log', '/sub1/_common.log');
//        add_log('Мой вложенный log', 'sub1/_common.log');
//        add_log('Мой вложенный log', 'subfolder2/subfolder3/second.log');
//        add_log('Мой вложенный log', './subfolder2/subfolder3/second.log');


        $this->render($tpl);
    }



    function qwe2(){
        
        die();

        $path = _ROOT_DIR_ . '/views/sites/money/sources/img/qr_restelecom.png';




        /* start qr reader */
        include_once(_ROOT_DIR_ . '/engine/core/common_api/qr-code-reader/lib/QrReader.php');
        $qrcode = new \Zxing\QrReader($path);
        $text = $qrcode->text();
        print $text;
        exit;
        /* finish qr reader */

        // Нужно будет так же попробовать распознание всех файлов в папке. Пример
        $dir = scandir('qrcodes');
        foreach($dir as $file) {
            if($file=='.'||$file=='..') continue;

            print $file;
            print ' --- ';
            $qrcode = new QrReader('qrcodes/'.$file);
            print $text = $qrcode->text();
            print "<br/>";
        }

//        $this->render($tpl);

        die();

        $qr_str = 't=20211203T1912&s=810.00&fn=9280440301269657&i=207239&fp=1459136215&n=1';
        parse_str($qr_str, $qr_arr);

        pr($qr_arr);

        require_once(_MODULES_ . '/receipt.php');
        $receipt = new money_out();
        $res = $receipt->do_save($qr_arr);

        pr($res);


        // Различные barcode сгенерированные на pdf
        // $this->_act_genetateBarcodesToPDF();

//        $this->render($tpl);
    }

    function _act_genetateBarcodesToPDF(){
        $tpl = get_template('', $this->module, 'barcode_test');

            //////////// QR /////////////
            // Вставляем qr код
//            require_once(_ROOT_DIR_ . 'engine/core/common_api/TCPDF/tcpdf_min_6_3_2/tcpdf.php');
//            require_once(_ROOT_DIR_ . 'engine/core/common_api/TCPDF/tcpdf_min_6_3_2/tcpdf_autoconfig.php');
//            require_once('/usr/share/php/tcpdf/tcpdf.php\',');
            require_once(_ROOT_DIR_ . 'engine/core/common_api/TCPDF/tcpdf_min_6_3_2/examples/tcpdf_include.php');
            // Include the main TCPDF library (search for installation path).

    // create new PDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Nicola Asuni');
            $pdf->SetTitle('TCPDF Example 050');
            $pdf->SetSubject('TCPDF Tutorial');
            $pdf->SetKeywords('TCPDF, PDF, example, test, guide');

    // set default header data
            $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 050', PDF_HEADER_STRING);

    // set header and footer fonts
            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    // set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // set margins
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // set some language-dependent strings (optional)
            if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                require_once(dirname(__FILE__).'/lang/eng.php');
                $pdf->setLanguageArray($l);
            }

    // ---------------------------------------------------------

    // NOTE: 2D barcode algorithms must be implemented on 2dbarcode.php class file.

    // set font
            $pdf->SetFont('helvetica', '', 11);

    // add a page
            $pdf->AddPage();

    // print a message
            $txt = "You can also export 2D barcodes in other formats (PNG, SVG, HTML). Check the examples inside the barcode directory.\n";
            $pdf->MultiCell(70, 50, $txt, 0, 'J', false, 1, 125, 30, true, 0, false, true, 0, 'T', false);


            $pdf->SetFont('helvetica', '', 10);

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    // set style for barcode
            $style = array(
                'border' => true,
                'vpadding' => 'auto',
                'hpadding' => 'auto',
                'fgcolor' => array(0,0,0),
                'bgcolor' => false, //array(255,255,255)
                'module_width' => 1, // width of a single module in points
                'module_height' => 1 // height of a single module in points
            );

    // write RAW 2D Barcode

            $code = '111011101110111,010010001000010,010011001110010,010010000010010,010011101110010';
            $pdf->write2DBarcode($code, 'RAW', 80, 30, 30, 20, $style, 'N');

    // write RAW2 2D Barcode
            $code = '[111011101110111][010010001000010][010011001110010][010010000010010][010011101110010]';
            $pdf->write2DBarcode($code, 'RAW2', 80, 60, 30, 20, $style, 'N');

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    // set style for barcode
            $style = array(
                'border' => 2,
                'vpadding' => 'auto',
                'hpadding' => 'auto',
                'fgcolor' => array(0,0,0),
                'bgcolor' => false, //array(255,255,255)
                'module_width' => 1, // width of a single module in points
                'module_height' => 1 // height of a single module in points
            );

    // QRCODE,L : QR-CODE Low error correction
            $pdf->write2DBarcode('www.tcpdf.org', 'QRCODE,L', 20, 30, 50, 50, $style, 'N');
            $pdf->Text(20, 25, 'QRCODE L');

    // QRCODE,M : QR-CODE Medium error correction
            $pdf->write2DBarcode('www.tcpdf.org', 'QRCODE,M', 20, 90, 50, 50, $style, 'N');
            $pdf->Text(20, 85, 'QRCODE M');

    // QRCODE,Q : QR-CODE Better error correction
            $pdf->write2DBarcode('www.tcpdf.org', 'QRCODE,Q', 20, 150, 50, 50, $style, 'N');
            $pdf->Text(20, 145, 'QRCODE Q');

    // QRCODE,H : QR-CODE Best error correction
            $pdf->write2DBarcode('www.tcpdf.org', 'QRCODE,H', 20, 210, 50, 50, $style, 'N');
            $pdf->Text(20, 205, 'QRCODE H');

    // -------------------------------------------------------------------
    // PDF417 (ISO/IEC 15438:2006)

            /*

             The $type parameter can be simple 'PDF417' or 'PDF417' followed by a
             number of comma-separated options:

             'PDF417,a,e,t,s,f,o0,o1,o2,o3,o4,o5,o6'

             Possible options are:

                 a  = aspect ratio (width/height);
                 e  = error correction level (0-8);

                 Macro Control Block options:

                 t  = total number of macro segments;
                 s  = macro segment index (0-99998);
                 f  = file ID;
                 o0 = File Name (text);
                 o1 = Segment Count (numeric);
                 o2 = Time Stamp (numeric);
                 o3 = Sender (text);
                 o4 = Addressee (text);
                 o5 = File Size (numeric);
                 o6 = Checksum (numeric).

             Parameters t, s and f are required for a Macro Control Block, all other parametrs are optional.
             To use a comma character ',' on text options, replace it with the character 255: "\xff".

            */

            $pdf->write2DBarcode('www.tcpdf.org', 'PDF417', 80, 90, 0, 30, $style, 'N');
            $pdf->Text(80, 85, 'PDF417 (ISO/IEC 15438:2006)');

    // -------------------------------------------------------------------
    // DATAMATRIX (ISO/IEC 16022:2006)

            $pdf->write2DBarcode('http://www.tcpdf.org', 'DATAMATRIX', 80, 150, 50, 50, $style, 'N');
            $pdf->Text(80, 145, 'DATAMATRIX (ISO/IEC 16022:2006)');

    // -------------------------------------------------------------------

    // new style
            $style = array(
                'border' => 2,
                'padding' => 'auto',
                'fgcolor' => array(0,0,255),
                'bgcolor' => array(255,255,64)
            );

    // QRCODE,H : QR-CODE Best error correction
            $pdf->write2DBarcode('www.tcpdf.org', 'QRCODE,H', 80, 210, 50, 50, $style, 'N');
            $pdf->Text(80, 205, 'QRCODE H - COLORED');

    // new style
            $style = array(
                'border' => false,
                'padding' => 0,
                'fgcolor' => array(128,0,0),
                'bgcolor' => false
            );

    // QRCODE,H : QR-CODE Best error correction
            $pdf->write2DBarcode('www.tcpdf.org', 'QRCODE,H', 140, 210, 50, 50, $style, 'N');
            $pdf->Text(140, 205, 'QRCODE H - NO PADDING');

    // ---------------------------------------------------------

    //Close and output PDF document
        ob_end_clean();
            $pdf->Output('example_051.pdf', 'I');

    //============================================================+
    // END OF FILE
    //============================================================+

        $this->render($tpl);
    }
}

?>

