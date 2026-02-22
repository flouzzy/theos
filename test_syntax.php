<?php
class Configurator {
    public function nullOnInvalid() {
        return "ok";
    }
}
$c = new Configurator();
$a = [
    new Configurator()->nullOnInvalid(),
];
echo $a[0];
