<?php
/**
 * Admin girişi ləğv edilib – vahid giriş səhifəsi (login.php) istifadə olunur.
 * Bu URL köhnə linklər üçün login.php-ə yönləndirir.
 */
session_start();
header('Location: ../login.php');
exit;
