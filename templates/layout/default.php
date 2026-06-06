<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>FatController Refactoring Exercise</title>
    <style>
        body { font-family: sans-serif; margin: 2rem; max-width: 48rem; }
        label { display: block; margin-top: 1rem; }
        input[type=text], textarea { display: block; width: 100%; padding: .5rem; }
        textarea { min-height: 8rem; }
        button { margin-top: 1rem; padding: .5rem 1rem; }
        .message { margin: 1rem 0; padding: .75rem; background: #eef; }
        .error { background: #fee; }
        .success { background: #efe; }
    </style>
</head>
<body>
    <?= $this->fetch('content') ?>
</body>
</html>
