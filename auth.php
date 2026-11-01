<?php
session_start();

function wajib_role(string $role): void
{
    if (($_SESSION['role'] ?? '') !== $role) {
        header('Location: login.php');
        exit;
    }
}