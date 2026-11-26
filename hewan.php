<?php

class Hewan {
    private $id;
    protected $nama;
    protected $umur;
    protected $gambarPath;
    protected $filePath;

    public function __construct($id, $nama, $umur, $gambarPath = null, $filePath = null) {
        $this->id = $id;
        $this->nama = $nama;
        $this->umur = $umur;
        $this->gambarPath = $gambarPath;
        $this->filePath = $filePath;
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getNama() {
        return $this->nama;
    }

    public function setNama($nama) {
        $this->nama = $nama;
    }

    public function getUmur() {
        return $this->umur;
    }

    public function setUmur($umur) {
        $this->umur = $umur;
    }

    public function getGambarPath() {
        return $this->gambarPath;
    }

    public function setGambarPath($gambarPath) {
        $this->gambarPath = $gambarPath;
    }

    public function getFilePath() {
        return $this->filePath;
    }

    public function setFilePath($filePath) {
        $this->filePath = $filePath;
    }

    public function displayInfo() {
        return "ID: {$this->id} | Nama: {$this->nama} | Umur: {$this->umur} Tahun";
    }

    public function bersuara() {
        return "Hewan ini mengeluarkan suara umum.";
    }
}

class Mamalia extends Hewan {
    private $jumlahKaki;

    public function __construct($id, $nama, $umur, $jumlahKaki, $gambarPath = null, $filePath = null) {
        parent::__construct($id, $nama, $umur, $gambarPath, $filePath);
        $this->jumlahKaki = $jumlahKaki;
    }

    public function getJumlahKaki() {
        return $this->jumlahKaki;
    }

    public function setJumlahKaki($jumlahKaki) {
        $this->jumlahKaki = $jumlahKaki;
    }

    public function displayInfo() {
        return parent::displayInfo() . " | Kaki: {$this->jumlahKaki} (Mamalia)";
    }

    public function bersuara() {
        return "Roar! Mengaum dengan keras.";
    }
}

class Burung extends Hewan {
    private $warnaBulu;

    public function __construct($id, $nama, $umur, $warnaBulu, $gambarPath = null, $filePath = null) {
        parent::__construct($id, $nama, $umur, $gambarPath, $filePath);
        $this->warnaBulu = $warnaBulu;
    }

    public function getWarnaBulu() {
        return $this->warnaBulu;
    }

    public function setWarnaBulu($warnaBulu) {
        $this->warnaBulu = $warnaBulu;
    }

    public function displayInfo() {
        return parent::displayInfo() . " | Bulu: {$this->warnaBulu} (Burung)";
    }

    public function bersuara() {
        return "Chirp-chirp! Berkicau dengan merdu.";
    }
}