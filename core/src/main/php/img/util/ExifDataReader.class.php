<?php
/* This class is part of the XP framework
 *
 * $Id$ 
 */

  uses('io.streams.InputStream', 'img.util.ExifData');

  /**
   * Reads the EXIF headers from JPEG or TIFF
   *
   * @see      php://exif_read_data
   */
  class ExifDataReader extends Object {

    // From TIFF 6.0 Specification, Image File Directory, subsection "Types"
    const BYTE      = 1;
    const ASCII     = 2;
    const USHORT    = 3;
    const ULONG     = 4;
    const URATIONAL = 5;
    const SBYTE     = 6;
    const UNDEFINED = 7;
    const SHORT     = 8;
    const LONG      = 9;
    const RATIONAL  = 10;
    const FLOAT     = 11;
    const DOUBLE    = 12;

    protected static $seg= array(
      "\xC0" => 'SOF0',  "\xC1" => 'SOF1',  "\xC2" => 'SOF2',  "\xC3" => 'SOF4',
      "\xC5" => 'SOF5',  "\xC6" => 'SOF6',  "\xC7" => 'SOF7',  "\xC8" => 'JPG',
      "\xC9" => 'SOF9',  "\xCA" => 'SOF10', "\xCB" => 'SOF11', "\xCD" => 'SOF13',
      "\xCE" => 'SOF14', "\xCF" => 'SOF15',
      "\xC4" => 'DHT',   "\xCC" => 'DAC',

      "\xD0" => 'RST0',  "\xD1" => 'RST1',  "\xD2" => 'RST2',  "\xD3" => 'RST3',
      "\xD4" => 'RST4',  "\xD5" => 'RST5',  "\xD6" => 'RST6',  "\xD7" => 'RST7',

      "\xD8" => 'SOI',   "\xD9" => 'EOI',   "\xDA" => 'SOS',   "\xDB" => 'DQT',
      "\xDC" => 'DNL',   "\xDD" => 'DRI',   "\xDE" => 'DHP',   "\xDF" => 'EXP',

      "\xE0" => 'APP0',  "\xE1" => 'APP1',  "\xE2" => 'APP2',  "\xE3" => 'APP3',
      "\xE4" => 'APP4',  "\xE5" => 'APP5',  "\xE6" => 'APP6',  "\xE7" => 'APP7',
      "\xE8" => 'APP8',  "\xE9" => 'APP9',  "\xEA" => 'APP10', "\xEB" => 'APP11',
      "\xEC" => 'APP12', "\xED" => 'APP13', "\xEE" => 'APP14', "\xEF" => 'APP15',


      "\xF0" => 'JPG0',  "\xF1" => 'JPG1',  "\xF2" => 'JPG2',  "\xF3" => 'JPG3',
      "\xF4" => 'JPG4',  "\xF5" => 'JPG5',  "\xF6" => 'JPG6',  "\xF7" => 'JPG7',
      "\xF8" => 'JPG8',  "\xF9" => 'JPG9',  "\xFA" => 'JPG10', "\xFB" => 'JPG11',
      "\xFC" => 'JPG12', "\xFD" => 'JPG13',

      "\xFE" => 'COM',   "\x01" => 'TEM',   "\x02" => 'RES'
    );

    // From PHP's exif.c
    protected static $tag= array(
      0x000B => 'ACDComment',
      0x00FE => 'NewSubFile', /* better name it 'ImageType' ? */
      0x00FF => 'SubFile',
      0x0100 => 'ImageWidth',
      0x0101 => 'ImageLength',
      0x0102 => 'BitsPerSample',
      0x0103 => 'Compression',
      0x0106 => 'PhotometricInterpretation',
      0x010A => 'FillOrder',
      0x010D => 'DocumentName',
      0x010E => 'ImageDescription',
      0x010F => 'Make',
      0x0110 => 'Model',
      0x0111 => 'StripOffsets',
      0x0112 => 'Orientation',
      0x0115 => 'SamplesPerPixel',
      0x0116 => 'RowsPerStrip',
      0x0117 => 'StripByteCounts',
      0x0118 => 'MinSampleValue',
      0x0119 => 'MaxSampleValue',
      0x011A => 'XResolution',
      0x011B => 'YResolution',
      0x011C => 'PlanarConfiguration',
      0x011D => 'PageName',
      0x011E => 'XPosition',
      0x011F => 'YPosition',
      0x0120 => 'FreeOffsets',
      0x0121 => 'FreeByteCounts',
      0x0122 => 'GrayResponseUnit',
      0x0123 => 'GrayResponseCurve',
      0x0124 => 'T4Options',
      0x0125 => 'T6Options',
      0x0128 => 'ResolutionUnit',
      0x0129 => 'PageNumber',
      0x012D => 'TransferFunction',
      0x0131 => 'Software',
      0x0132 => 'DateTime',
      0x013B => 'Artist',
      0x013C => 'HostComputer',
      0x013D => 'Predictor',
      0x013E => 'WhitePoint',
      0x013F => 'PrimaryChromaticities',
      0x0140 => 'ColorMap',
      0x0141 => 'HalfToneHints',
      0x0142 => 'TileWidth',
      0x0143 => 'TileLength',
      0x0144 => 'TileOffsets',
      0x0145 => 'TileByteCounts',
      0x014A => 'SubIFD',
      0x014C => 'InkSet',
      0x014D => 'InkNames',
      0x014E => 'NumberOfInks',
      0x0150 => 'DotRange',
      0x0151 => 'TargetPrinter',
      0x0152 => 'ExtraSample',
      0x0153 => 'SampleFormat',
      0x0154 => 'SMinSampleValue',
      0x0155 => 'SMaxSampleValue',
      0x0156 => 'TransferRange',
      0x0157 => 'ClipPath',
      0x0158 => 'XClipPathUnits',
      0x0159 => 'YClipPathUnits',
      0x015A => 'Indexed',
      0x015B => 'JPEGTables',
      0x015F => 'OPIProxy',
      0x0200 => 'JPEGProc',
      0x0201 => 'JPEGInterchangeFormat',
      0x0202 => 'JPEGInterchangeFormatLength',
      0x0203 => 'JPEGRestartInterval',
      0x0205 => 'JPEGLosslessPredictors',
      0x0206 => 'JPEGPointTransforms',
      0x0207 => 'JPEGQTables',
      0x0208 => 'JPEGDCTables',
      0x0209 => 'JPEGACTables',
      0x0211 => 'YCbCrCoefficients',
      0x0212 => 'YCbCrSubSampling',
      0x0213 => 'YCbCrPositioning',
      0x0214 => 'ReferenceBlackWhite',
      0x02BC => 'ExtensibleMetadataPlatform', /* XAP: Extensible Authoring Publishing, obsoleted by XMP: Extensible Metadata Platform */
      0x0301 => 'Gamma',
      0x0302 => 'ICCProfileDescriptor',
      0x0303 => 'SRGBRenderingIntent',
      0x0320 => 'ImageTitle',
      0x5001 => 'ResolutionXUnit',
      0x5002 => 'ResolutionYUnit',
      0x5003 => 'ResolutionXLengthUnit',
      0x5004 => 'ResolutionYLengthUnit',
      0x5005 => 'PrintFlags',
      0x5006 => 'PrintFlagsVersion',
      0x5007 => 'PrintFlagsCrop',
      0x5008 => 'PrintFlagsBleedWidth',
      0x5009 => 'PrintFlagsBleedWidthScale',
      0x500A => 'HalftoneLPI',
      0x500B => 'HalftoneLPIUnit',
      0x500C => 'HalftoneDegree',
      0x500D => 'HalftoneShape',
      0x500E => 'HalftoneMisc',
      0x500F => 'HalftoneScreen',
      0x5010 => 'JPEGQuality',
      0x5011 => 'GridSize',
      0x5012 => 'ThumbnailFormat',
      0x5013 => 'ThumbnailWidth',
      0x5014 => 'ThumbnailHeight',
      0x5015 => 'ThumbnailColorDepth',
      0x5016 => 'ThumbnailPlanes',
      0x5017 => 'ThumbnailRawBytes',
      0x5018 => 'ThumbnailSize',
      0x5019 => 'ThumbnailCompressedSize',
      0x501A => 'ColorTransferFunction',
      0x501B => 'ThumbnailData',
      0x5020 => 'ThumbnailImageWidth',
      0x5021 => 'ThumbnailImageHeight',
      0x5022 => 'ThumbnailBitsPerSample',
      0x5023 => 'ThumbnailCompression',
      0x5024 => 'ThumbnailPhotometricInterp',
      0x5025 => 'ThumbnailImageDescription',
      0x5026 => 'ThumbnailEquipMake',
      0x5027 => 'ThumbnailEquipModel',
      0x5028 => 'ThumbnailStripOffsets',
      0x5029 => 'ThumbnailOrientation',
      0x502A => 'ThumbnailSamplesPerPixel',
      0x502B => 'ThumbnailRowsPerStrip',
      0x502C => 'ThumbnailStripBytesCount',
      0x502D => 'ThumbnailResolutionX',
      0x502E => 'ThumbnailResolutionY',
      0x502F => 'ThumbnailPlanarConfig',
      0x5030 => 'ThumbnailResolutionUnit',
      0x5031 => 'ThumbnailTransferFunction',
      0x5032 => 'ThumbnailSoftwareUsed',
      0x5033 => 'ThumbnailDateTime',
      0x5034 => 'ThumbnailArtist',
      0x5035 => 'ThumbnailWhitePoint',
      0x5036 => 'ThumbnailPrimaryChromaticities',
      0x5037 => 'ThumbnailYCbCrCoefficients',
      0x5038 => 'ThumbnailYCbCrSubsampling',
      0x5039 => 'ThumbnailYCbCrPositioning',
      0x503A => 'ThumbnailRefBlackWhite',
      0x503B => 'ThumbnailCopyRight',
      0x5090 => 'LuminanceTable',
      0x5091 => 'ChrominanceTable',
      0x5100 => 'FrameDelay',
      0x5101 => 'LoopCount',
      0x5110 => 'PixelUnit',
      0x5111 => 'PixelPerUnitX',
      0x5112 => 'PixelPerUnitY',
      0x5113 => 'PaletteHistogram',
      0x1000 => 'RelatedImageFileFormat',
      0x800D => 'ImageID',
      0x80E3 => 'Matteing',   /* obsoleted by ExtraSamples */
      0x80E4 => 'DataType',   /* obsoleted by SampleFormat */
      0x80E5 => 'ImageDepth',
      0x80E6 => 'TileDepth',
      0x828D => 'CFARepeatPatternDim',
      0x828E => 'CFAPattern',
      0x828F => 'BatteryLevel',
      0x8298 => 'Copyright',
      0x829A => 'ExposureTime',
      0x829D => 'FNumber',
      0x83BB => 'IPTC/NAA',
      0x84E3 => 'IT8RasterPadding',
      0x84E5 => 'IT8ColorTable',
      0x8649 => 'ImageResourceInformation', /* PhotoShop */
      0x8769 => 'Exif_IFD_Pointer',
      0x8773 => 'ICC_Profile',
      0x8822 => 'ExposureProgram',
      0x8824 => 'SpectralSensity',
      0x8828 => 'OECF',
      0x8825 => 'GPS_IFD_Pointer',
      0x8827 => 'ISOSpeedRatings',
      0x8828 => 'OECF',
      0x9000 => 'ExifVersion',
      0x9003 => 'DateTimeOriginal',
      0x9004 => 'DateTimeDigitized',
      0x9101 => 'ComponentsConfiguration',
      0x9102 => 'CompressedBitsPerPixel',
      0x9201 => 'ShutterSpeedValue',
      0x9202 => 'ApertureValue',
      0x9203 => 'BrightnessValue',
      0x9204 => 'ExposureBiasValue',
      0x9205 => 'MaxApertureValue',
      0x9206 => 'SubjectDistance',
      0x9207 => 'MeteringMode',
      0x9208 => 'LightSource',
      0x9209 => 'Flash',
      0x920A => 'FocalLength',
      0x920B => 'FlashEnergy',                 /* 0xA20B  in JPEG   */
      0x920C => 'SpatialFrequencyResponse',    /* 0xA20C    -  -    */
      0x920D => 'Noise',
      0x920E => 'FocalPlaneXResolution',       /* 0xA20E    -  -    */
      0x920F => 'FocalPlaneYResolution',       /* 0xA20F    -  -    */
      0x9210 => 'FocalPlaneResolutionUnit',    /* 0xA210    -  -    */
      0x9211 => 'ImageNumber',
      0x9212 => 'SecurityClassification',
      0x9213 => 'ImageHistory',
      0x9214 => 'SubjectLocation',             /* 0xA214    -  -    */
      0x9215 => 'ExposureIndex',               /* 0xA215    -  -    */
      0x9216 => 'TIFF/EPStandardID',
      0x9217 => 'SensingMethod',               /* 0xA217    -  -    */
      0x923F => 'StoNits',
      0x927C => 'MakerNote',
      0x9286 => 'UserComment',
      0x9290 => 'SubSecTime',
      0x9291 => 'SubSecTimeOriginal',
      0x9292 => 'SubSecTimeDigitized',
      0x935C => 'ImageSourceData',             /* "Adobe Photoshop Document Data Block": 8BIM... */
      0x9c9b => 'Title',                       /* Win XP specific, Unicode  */
      0x9c9c => 'Comments',                    /* Win XP specific, Unicode  */
      0x9c9d => 'Author',                      /* Win XP specific, Unicode  */
      0x9c9e => 'Keywords',                    /* Win XP specific, Unicode  */
      0x9c9f => 'Subject',                     /* Win XP specific, Unicode, not to be confused with SubjectDistance and SubjectLocation */
      0xA000 => 'FlashPixVersion',
      0xA001 => 'ColorSpace',
      0xA002 => 'ExifImageWidth',
      0xA003 => 'ExifImageLength',
      0xA004 => 'RelatedSoundFile',
      0xA005 => 'InteroperabilityOffset',
      0xA20B => 'FlashEnergy',                 /* 0x920B in TIFF/EP */
      0xA20C => 'SpatialFrequencyResponse',    /* 0x920C    -  -    */
      0xA20D => 'Noise',
      0xA20E => 'FocalPlaneXResolution',       /* 0x920E    -  -    */
      0xA20F => 'FocalPlaneYResolution',       /* 0x920F    -  -    */
      0xA210 => 'FocalPlaneResolutionUnit',    /* 0x9210    -  -    */
      0xA211 => 'ImageNumber',
      0xA212 => 'SecurityClassification',
      0xA213 => 'ImageHistory',
      0xA214 => 'SubjectLocation',             /* 0x9214    -  -    */
      0xA215 => 'ExposureIndex',               /* 0x9215    -  -    */
      0xA216 => 'TIFF/EPStandardID',
      0xA217 => 'SensingMethod',               /* 0x9217    -  -    */
      0xA300 => 'FileSource',
      0xA301 => 'SceneType',
      0xA302 => 'CFAPattern',
      0xA401 => 'CustomRendered',
      0xA402 => 'ExposureMode',
      0xA403 => 'WhiteBalance',
      0xA404 => 'DigitalZoomRatio',
      0xA405 => 'FocalLengthIn35mmFilm',
      0xA406 => 'SceneCaptureType',
      0xA407 => 'GainControl',
      0xA408 => 'Contrast',
      0xA409 => 'Saturation',
      0xA40A => 'Sharpness',
      0xA40B => 'DeviceSettingDescription',
      0xA40C => 'SubjectDistanceRange',
      0xA420 => 'ImageUniqueID',
    );

    protected $name= '';
    protected $stream= NULL;
    protected $offset= 0;

    /**
     * Creates a new EXIF data reader instance
     * *
     * @param  io.streams.InputStream $in The input stream to read from
     * @param  string $name The input stream's name
     * @throws lang.FormatException if the input stream cannot be parsed 
     */
    public function __construct(InputStream $in, $name= 'input stream') {
      if ("\xff\xd8\xff" !== $in->read(3)) {
        throw new FormatException('Could not find start of image marker in JPEG data '.$name);
      }

      $this->offset= 3;
      $this->stream= $in;
      $this->name= $name;

      // Parse JPEG headers
      $this->headers= array();
      while ("\xd9" !== ($marker= $this->stream->read(1))) {
        $this->offset++;
        if ("\xda" === $marker) break;      // Stop at SOS (Start Of Scan)

        if ($marker < "\xd0" || $marker > "\xd7") {
          $size= current(unpack('n', $this->stream->read(2)));
          $seg= self::$seg[$marker];
          if (!isset($this->headers[$seg])) $this->headers[$seg]= array();
          $this->headers[$seg][]= array(
            'type'   => $marker,
            'offset' => $this->offset,
            'size'   => $size,
            'bytes'   => $this->stream->read($size - 2)
          );
          $this->offset+= $size;
        }

        if ("\xff" !== ($c= $this->stream->read(1))) {
          throw new FormatException(sprintf(
            'JPEG header corrupted, have x%02x, expecting xff at offset %d',
            ord($c),
            $this->offset
          ));
        }
        $this->offset++;
      }
    }

    /**
     * Read IFD entries
     *
     * @param  string $data The EXIF data
     * @param  int $offset The offset to start at
     * @param  [:string] $format The unpack() formats
     * @return [:var] IFD
     */
    protected function readIFD($data, &$offset, $tags, $format) {
      static $length= array(
        self::BYTE      => 1,
        self::ASCII     => 1,
        self::USHORT    => 2,
        self::ULONG     => 4,
        self::URATIONAL => 8,
        self::SBYTE     => 1,
        self::UNDEFINED => 1,
        self::SHORT     => 2,
        self::LONG      => 4,
        self::RATIONAL  => 8,
        self::FLOAT     => 4,
        self::DOUBLE    => 8
      );
      static $sub= array(
        0x8769 => TRUE,           // Exif_IFD_Pointer, inherit tags
        0x8825 => array(          // GPS_IFD_Pointer, defines own tags
          0x0000 => 'GPSVersion',
          0x0001 => 'GPSLatitudeRef',
          0x0002 => 'GPSLatitude',
          0x0003 => 'GPSLongitudeRef',
          0x0004 => 'GPSLongitude',
          0x0005 => 'GPSAltitudeRef',
          0x0006 => 'GPSAltitude',
          0x0007 => 'GPSTimeStamp',
          0x0008 => 'GPSSatellites',
          0x0009 => 'GPSStatus',
          0x000A => 'GPSMeasureMode',
          0x000B => 'GPSDOP',
          0x000C => 'GPSSpeedRef',
          0x000D => 'GPSSpeed',
          0x000E => 'GPSTrackRef',
          0x000F => 'GPSTrack',
          0x0010 => 'GPSImgDirectionRef',
          0x0011 => 'GPSImgDirection',
          0x0012 => 'GPSMapDatum',
          0x0013 => 'GPSDestLatitudeRef',
          0x0014 => 'GPSDestLatitude',
          0x0015 => 'GPSDestLongitudeRef',
          0x0016 => 'GPSDestLongitude',
          0x0017 => 'GPSDestBearingRef',
          0x0018 => 'GPSDestBearing',
          0x0019 => 'GPSDestDistanceRef',
          0x001A => 'GPSDestDistance',
          0x001B => 'GPSProcessingMode',
          0x001C => 'GPSAreaInformation',
          0x001D => 'GPSDateStamp',
          0x001E => 'GPSDifferential'
        )
      );

      $entries= current(unpack($format[self::USHORT], substr($data, $offset, 2)));
      $offset+= 2;

      $return= array();
      for ($i= 0; $i < $entries; $i++) {
        $entry= unpack(
          $format[self::USHORT].'tag/'.$format[self::USHORT].'type/'.$format[self::ULONG].'size', 
          substr($data, $offset, 8)
        );
        $offset+= 8;

        $l= $entry['size'] * $length[$entry['type']];
        if ($l > 4) {
          $entry['offset']= current(unpack($format[self::ULONG], substr($data, $offset, 4)));
          $read= $entry['offset']+ 6;
        } else {
          $entry['offset']= NULL;   // Fit into 4 bytes
          $read= $offset;
        }
        $offset+= 4;

        // Recursively extract Sub-IFDs
        if (isset($sub[$entry['tag']])) {
          $start= current(unpack($format[$entry['type']], substr($data, $read, $l)));
          $read= $start + 6;
          $entry['data']= $this->readIFD($data, $read, TRUE === $sub[$entry['tag']] ? self::$tag : $sub[$entry['tag']], $format);
        } else {
          $entry['data']= implode('/', unpack($format[$entry['type']], substr($data, $read, $l)));
        }

        $t= isset($tags[$entry['tag']]) ? $tags[$entry['tag']] : sprintf('UndefinedTag:0x%04X', $entry['tag']);
        $return[$t]= $entry;
      }

      return $return;
    }

    /**
     * Interprets headers and aggregates this into headers map
     * 
     * @return [:var]
     */
    public function headers() {
      static $pack= array(
        'MM' => array(self::USHORT => 'n', self::ULONG => 'N', self::URATIONAL => 'N2', self::ASCII => 'a*'),
        'II' => array(self::USHORT => 'v', self::ULONG => 'V', self::URATIONAL => 'V2', self::ASCII => 'a*'),
      );

      // SOF0
      if (isset($this->headers['SOF0'])) {
        foreach ($this->headers['SOF0'] as $i => $header) {
          $this->headers['SOF0']['data']= unpack('Cbits/nheight/nwidth/Cchannels', $header['bytes']);
        }
      }

      // Check APP1 header for "Exif" marker 
      if (isset($this->headers['APP1'])) {
        $this->headers['APP1']['data']= array();
        foreach ($this->headers['APP1'] as $i => $header) {
          $marker= strtok($header['bytes'], "\0");
          if ('Exif' === $marker) {
            $offset= 0;
            $tiff= unpack('x4id/x2nul/a2align', substr($header['bytes'], $offset, 8));
            $offset+= 8;

            // TIFF Header, part 2: Magic number / first IFD offset
            $tiff= array_merge($tiff, unpack(
              $pack[$tiff['align']][self::USHORT].'magic/'.$pack[$tiff['align']][self::ULONG].'ifd1', 
              substr($header['bytes'], $offset, 6)
            ));
            $offset+= 6;
            if (42 !== $tiff['magic']) {
              throw new FormatException('Malformed EXIF data - magic number mismatch at offset '.$offset);
            }

            // Read IFDs
            $n= $tiff['ifd1'];
            $data= array();
            do {
              $offset= $n + 6;
              $data= array_merge($data, $this->readIFD($header['bytes'], $offset, self::$tag, $pack[$tiff['align']]));
              $n= current(unpack($pack[$tiff['align']][self::ULONG], substr($header['bytes'], $offset, 4)));
            } while ($n > 0 && $n < strlen($this->headers['APP1']['bytes']));
          } else {
            $data= NULL;
          }
          $this->headers['APP1']['data'][$marker]= $data;
        }
      }

      return $this->headers;
    }

    /**
     * Lookup helper
     *
     * @param   [:var] exif
     * @param   string... key
     * @return  string value or NULL
     */
    protected static function lookup($exif) {
      for ($i= 1, $s= func_num_args(); $i < $s; $i++) {
        $key= func_get_arg($i);
        if (isset($exif[$key])) return $exif[$key]['data'];
      }
      return NULL;
    }

    /**
     * Reads the data
     * 
     * @return img.util.ExifData
     */
    public function read() {
      with ($data= new ExifData(), $headers= $this->headers()); {
        $data->setFileName($this->name);
        $data->setFileSize(-1);
        $data->setMimeType('image/jpeg');

        $data->setWidth($headers['SOF0']['data']['width']);
        $data->setHeight($headers['SOF0']['data']['height']);

        $data->setMake(trim(self::lookup($headers['APP1']['data']['Exif'], 'Make')));
        $data->setModel(trim(self::lookup($headers['APP1']['data']['Exif'], 'Model')));
        $data->setSoftware(self::lookup($headers['APP1']['data']['Exif'], 'Software'));

        $exif= $headers['APP1']['data']['Exif']['Exif_IFD_Pointer']['data'];

        if (NULL === ($a= self::lookup($exif, 'ApertureValue', 'MaxApertureValue', 'FNumber'))) {
          $data->setApertureFNumber(NULL);
        } else {
          sscanf($a, '%d/%d', $n, $frac);
          $data->setApertureFNumber(sprintf('f/%.1F', $n / $frac));
        }
        $data->setExposureTime(self::lookup($exif, 'ExposureTime'));
        $data->setExposureProgram(self::lookup($exif, 'ExposureProgram'));
        $data->setMeteringMode(self::lookup($exif, 'MeteringMode'));
        $data->setIsoSpeedRatings(self::lookup($exif, 'ISOSpeedRatings'));

        // Sometimes white balance is in MAKERNOTE - e.g. FUJIFILM's Finepix
        if (NULL !== ($w= self::lookup($exif, 'WhiteBalance'))) {
          $data->setWhiteBalance($w);
        } else if (0) { // isset($info['MAKERNOTE']) && NULL !== ($w= self::lookup($info['MAKERNOTE'], 'whitebalance'))) {
          $data->setWhiteBalance($w);
        } else {
          $data->setWhiteBalance(NULL);
        }

        // Extract focal length. Some models store "80" as "80/1", rip off
        // the divisor "1" in this case.
        if (NULL !== ($l= self::lookup($exif, 'FocalLength'))) {
          sscanf($l, '%d/%d', $n, $frac);
          $data->setFocalLength(1 == $frac ? $n : $n.'/'.$frac);
        } else {
          $data->setFocalLength(NULL);
        }

        // Check for Flash and flashUsed keys
        if (NULL !== ($f= self::lookup($exif, 'Flash'))) {
          $data->setFlash($f);
        } else {
          $data->setFlash(NULL);
        }

        if (NULL !== ($date= self::lookup($exif, 'DateTimeOriginal', 'DateTimeDigitized', 'DateTime'))) {
          $t= sscanf($date, '%4d:%2d:%2d %2d:%2d:%2d');
          $data->setDateTime(new Date(mktime($t[3], $t[4], $t[5], $t[1], $t[2], $t[0])));
        }

        if (NULL !== ($o= self::lookup($exif, 'Orientation'))) {
          $data->setOrientation($o);
        } else {
          $data->setOrientation(($data->width / $data->height) > 1.0
            ? 1   // normal
            : 5   // transpose
          );
        }

        return $data;
      }
    }
  }
?>
