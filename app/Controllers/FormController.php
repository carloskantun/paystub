<?php
namespace App\Controllers;

class FormController
{
    public function showForm()
    {
        // Render the form view
        include __DIR__ . '/../Views/form.php';
    }

    public function preview()
    {
        // Render the preview view
        include __DIR__ . '/../Views/preview.php';
    }
}
