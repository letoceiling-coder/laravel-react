<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .admin-header {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .admin-header h1 {
            font-size: 1.5rem;
            color: #2563eb;
        }
        
        .admin-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .welcome-message {
            text-align: center;
            padding: 3rem 0;
        }
        
        .welcome-message h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #1f2937;
        }
        
        .welcome-message p {
            font-size: 1.1rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Админ-панель</h1>
        </div>
        
        <div class="admin-content">
            <div class="welcome-message">
                <h2>Добро пожаловать в админ-панель</h2>
                <p>Здесь будет управление контентом сайта</p>
            </div>
        </div>
    </div>
</body>
</html>

