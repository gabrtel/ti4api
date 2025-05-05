<?php 


function validFaction($faction){
    return in_array(strtolower($faction), [
    "arborec",
    "letnev",
    "saar",
    "muaat",
    "hacan",
    "sol",
    "creuss",
    "l1z1x",
    "mentak",
    "naalu",
    "nekro",
    "sardakk",
    "jolnar",
    "xxcha",
    "yin",
    "yssaril",
    "argent",
    "empyrean",
    "manhact",
    "naazrokha",
    "nomad",
    "titans",
    "cabal",
    "council",
    ]);
}