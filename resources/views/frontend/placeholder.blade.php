<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сайт в разработке</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .container {
            text-align: center;
            z-index: 1;
            position: relative;
        }
        
        .message {
            background: rgba(255, 255, 255, 0.95);
            padding: 3rem 4rem;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            animation: fadeInUp 0.8s ease-out;
        }
        
        h1 {
            font-size: 2.5rem;
            color: #1f2937;
            margin-bottom: 1rem;
            animation: pulse 2s ease-in-out infinite;
        }
        
        .rocket {
            font-size: 4rem;
            display: inline-block;
            animation: floating 3s ease-in-out infinite;
            margin-bottom: 1rem;
        }
        
        p {
            font-size: 1.2rem;
            color: #6b7280;
            margin-top: 1rem;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        @keyframes floating {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            25% {
                transform: translateY(-20px) rotate(-5deg);
            }
            50% {
                transform: translateY(-10px) rotate(5deg);
            }
            75% {
                transform: translateY(-20px) rotate(-5deg);
            }
        }
        
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float 15s infinite;
        }
        
        @keyframes float {
            0% {
                transform: translateY(100vh) translateX(0) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(100px) rotate(360deg);
                opacity: 0;
            }
        }
        
        .particle:nth-child(1) { left: 10%; width: 80px; height: 80px; animation-delay: 0s; }
        .particle:nth-child(2) { left: 20%; width: 60px; height: 60px; animation-delay: 2s; }
        .particle:nth-child(3) { left: 30%; width: 100px; height: 100px; animation-delay: 4s; }
        .particle:nth-child(4) { left: 40%; width: 70px; height: 70px; animation-delay: 6s; }
        .particle:nth-child(5) { left: 50%; width: 90px; height: 90px; animation-delay: 8s; }
        .particle:nth-child(6) { left: 60%; width: 50px; height: 50px; animation-delay: 10s; }
        .particle:nth-child(7) { left: 70%; width: 85px; height: 85px; animation-delay: 12s; }
        .particle:nth-child(8) { left: 80%; width: 65px; height: 65px; animation-delay: 14s; }
        
        @media (max-width: 768px) {
            .message {
                padding: 2rem 2rem;
                margin: 1rem;
            }
            
            h1 {
                font-size: 1.8rem;
            }
            
            .rocket {
                font-size: 3rem;
            }
            
            p {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    
    <div class="container">
        <div class="message">
            <div class="rocket">🚀</div>
            <h1>Сайт в разработке. Скоро вас удивим 🚀</h1>
            <p>Мы работаем над чем-то особенным</p>
        </div>
    </div>
</body>
</html>

