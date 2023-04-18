<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Learn To Drive</title>
    <style>
        body {
            background-color: #3B3B3B;
        }
        .logo {
            width: 100px;
            height: 100px;
            background-color: #FFDE17;
        }
        h1 {
            color: #3B3B3B;
            font-size: 36px;
            margin: 20px 0;
        }
        p {
            color: #3B3B3B;
            font-size: 18px;
        }
        .container {
            /* display: flex;
            justify-content: center;
            align-items: center; */
            box-align: center;
            /* height: 100vh; */
            width: 400px;
            height: 200px;
            margin: auto;
            margin-top: 100px;
            background-color: #3B3B3B;
        }
        .login-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #ffde17;
            color: #3b3b3b;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 16px;
            cursor: pointer;
        }
    </style>
  </head>
  <body>
    @if (Route::has('login'))
        <div class="sm:fixed sm:top-0 sm:right-0 p-6 text-right">
            @auth
                <a href="{{ url('/home') }}" class="login-btn">Home</a>
            @else
                <a href="{{ route('login') }}" class="login-btn">Log in</a>
            @endauth
        </div>
    @endif
    <div class="container">
        <div style="text-align:center;background-color:#3B3B3B;padding:20px;">
            <x-application-logo />
            <h1 style="color:white;font-size:36px;margin-top:10px;">Learn To Drive</h1>
            <p style="color:white;font-size:18px;margin-top:10px;">"Learn to Drive" is a comprehensive application designed to aid individuals in preparing for the driving license test in Nepal, featuring multiple-choice questions, driving tips, instructions, and the ability to attempt mock tests.</p>
          </div>
      </div>

  </body>
</html>
