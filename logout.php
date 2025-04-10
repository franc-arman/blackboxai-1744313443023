<?php
require_once 'includes/functions.php';

// Destroy the session
session_destroy();

// Redirect to home page with message
redirect('index.php', 'You have been successfully logged out.', 'success');
