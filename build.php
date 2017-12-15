<?php

$p=new Phar("gate.phar",0,"gate.phar");$p->buildFromDirectory(__DIR__."/project");$p->setDefaultStub("bot.php");
