<?php
$pageTitle = "HSC Physics 1st Paper — Chapter 2: ভেক্টর";
$currentYear = date("Y");
?>
<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="HSC Physics 1st Paper Chapter 2 Vector — Bengali textbook-style lecture notes with LaTeX equations, examples, and MCQ practice.">
    <script>
        window.MathJax = {
            tex: {
                inlineMath: [
                    ['\\(', '\\)'],
                    ['$', '$']
                ],
                displayMath: [
                    ['\\[', '\\]'],
                    ['$$', '$$']
                ]
            },
            options: {
                skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code']
            },
            chtml: {
                scale: 1.03
            }
        };
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml.js"></script>
    <style>
        :root {
            --ink: #182638;
            --muted: #526274;
            --blue: #174a7e;
            --blue2: #2869a8;
            --pale: #edf5fc;
            --line: #d6e0e9;
            --paper: #fff;
            --bg: #edf1f5;
            --green: #e8f7ef;
            --amber: #fff6df
        }

        * {
            box-sizing: border-box
        }

        html {
            scroll-behavior: smooth
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: "Noto Sans Bengali", "Hind Siliguri", "SolaimanLipi", system-ui, sans-serif;
            line-height: 1.8
        }

        header {
            background: linear-gradient(130deg, #12385f, #2369a4);
            color: #fff;
            padding: 32px 20px 28px
        }

        header .wrap {
            max-width: 1080px;
            margin: auto
        }

        header h1 {
            font-size: clamp(1.65rem, 4vw, 2.45rem);
            line-height: 1.35;
            margin: 0 0 10px
        }

        header p {
            margin: 0;
            color: #e3effb
        }

        .layout {
            max-width: 1080px;
            margin: 22px auto;
            padding: 0 14px;
            display: grid;
            grid-template-columns: 250px minmax(0, 1fr);
            gap: 20px;
            align-items: start
        }

        nav,
        .content {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 13px;
            box-shadow: 0 3px 14px #18334d0a
        }

        nav {
            padding: 17px;
            position: sticky;
            top: 12px;
            max-height: calc(100vh - 24px);
            overflow: auto
        }

        nav strong {
            display: block;
            color: var(--blue);
            margin-bottom: 8px
        }

        nav a {
            display: block;
            padding: 5px 8px;
            color: #334c64;
            text-decoration: none;
            border-radius: 6px;
            font-size: .93rem
        }

        nav a:hover {
            background: var(--pale);
            color: var(--blue)
        }

        .content {
            padding: clamp(18px, 3vw, 34px);
            min-width: 0
        }

        .section {
            scroll-margin-top: 18px;
            margin-bottom: 34px
        }

        .section h2 {
            font-size: 1.48rem;
            color: var(--blue);
            border-bottom: 2px solid var(--line);
            padding-bottom: 8px;
            margin: 0 0 14px
        }

        .section h3 {
            font-size: 1.12rem;
            color: #244e76;
            margin: 22px 0 8px
        }

        .lead {
            font-size: 1.06rem
        }

        .callout {
            padding: 13px 16px;
            border-left: 4px solid var(--blue2);
            background: var(--pale);
            border-radius: 7px;
            margin: 14px 0
        }

        .tip {
            background: var(--green);
            border-left-color: #23935b
        }

        .warn {
            background: var(--amber);
            border-left-color: #c58a10
        }

        .eq {
            overflow-x: auto;
            text-align: center;
            background: #f8fafc;
            border: 1px solid #e0e8ef;
            border-radius: 8px;
            padding: 14px 10px;
            margin: 12px 0;
            font-size: 1.06rem;
            color: #102b46
        }

        .eq .label {
            display: block;
            text-align: right;
            color: #617286;
            font-size: .78rem;
            margin-top: 6px
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: .95rem
        }

        th,
        td {
            border: 1px solid var(--line);
            padding: 9px 11px;
            text-align: left;
            vertical-align: top
        }

        th {
            background: #eaf2f9;
            color: #204a70
        }

        ul,
        ol {
            padding-left: 25px
        }

        .formula-list li {
            margin: 8px 0
        }

        .example {
            border: 1px solid var(--line);
            border-radius: 9px;
            padding: 15px 17px;
            margin: 14px 0
        }

        .example h3 {
            margin-top: 0
        }

        .step {
            margin: 7px 0
        }

        .pill {
            display: inline-block;
            background: #e4effa;
            color: #214f7c;
            border-radius: 99px;
            padding: 2px 9px;
            font-size: .8rem
        }

        .quiz {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 14px 16px;
            margin: 12px 0
        }

        .quiz p {
            margin: 0 0 9px;
            font-weight: 600
        }

        .quiz label {
            display: block;
            margin: 5px 0;
            cursor: pointer
        }

        .feedback {
            margin-top: 8px;
            font-weight: 600
        }

        .correct {
            color: #147444
        }

        .incorrect {
            color: #a33131
        }

        button,
        .button {
            background: var(--blue);
            color: #fff;
            border: 0;
            border-radius: 7px;
            padding: 10px 15px;
            font: inherit;
            cursor: pointer;
            text-decoration: none;
            display: inline-block
        }

        button:hover,
        .button:hover {
            background: var(--blue2)
        }

        .toolbar {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
            margin-top: 17px
        }

        footer {
            text-align: center;
            color: var(--muted);
            padding: 20px;
            font-size: .88rem
        }

        .math-note {
            font-size: .9rem;
            color: var(--muted)
        }

        @media(max-width:800px) {
            .layout {
                grid-template-columns: 1fr
            }

            nav {
                position: relative;
                top: auto;
                max-height: none
            }

            nav .links {
                columns: 2
            }
        }

        @media(max-width:480px) {
            nav .links {
                columns: 1
            }

            .content {
                padding: 16px
            }

            table {
                font-size: .86rem
            }

            th,
            td {
                padding: 7px
            }
        }

        @media print {
            body {
                background: white;
                font-size: 11pt
            }

            header {
                background: white;
                color: #111;
                padding: 0 0 15px;
                border-bottom: 2px solid #333
            }

            header p {
                color: #333
            }

            .layout {
                display: block;
                max-width: none;
                margin: 0;
                padding: 0
            }

            nav,
            .toolbar,
            .quiz button,
            footer {
                display: none !important
            }

            .content {
                border: 0;
                box-shadow: none;
                padding: 0
            }

            .section {
                break-inside: avoid
            }

            .eq {
                background: white;
                break-inside: avoid
            }

            a {
                color: inherit;
                text-decoration: none
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="wrap">
            <span class="pill">HSC Physics 1st Paper • NCTB-aligned study notes</span>
            <h1>অধ্যায় ২: ভেক্টর (Vector)</h1>
            <p>সংজ্ঞা, প্রকারভেদ, ভেক্টর যোগ-বিয়োগ, উপাংশ, ডট ও ক্রস গুণন, এবং সমাধানসহ অনুশীলন</p>
            <div class="toolbar"><button onclick="window.print()">প্রিন্ট / Save as PDF</button><button onclick="document.querySelectorAll('.quiz').forEach(q=>{q.querySelectorAll('input').forEach(i=>i.checked=false);q.querySelector('.feedback').textContent=''})">MCQ রিসেট</button></div>
        </div>
    </header>
    <div class="layout">
        <nav aria-label="Chapter navigation"><strong>এই অধ্যায়ে</strong>
            <div class="links">
                <a href="#intro">১. ভেক্টরের ধারণা</a><a href="#types">২. ভেক্টরের প্রকারভেদ</a><a href="#addition">৩. যোগ ও বিয়োগ</a><a href="#components">৪. উপাংশ ও একক ভেক্টর</a><a href="#products">৫. ডট ও ক্রস গুণন</a><a href="#resolution">৬. লব্ধি ও প্রয়োগ</a><a href="#examples">৭. সমাধান করা উদাহরণ</a><a href="#formula">৮. সূত্রসংকলন</a><a href="#practice">৯. অনুশীলনী</a><a href="#quiz">১০. MCQ পরীক্ষা</a>
            </div>
        </nav>
        <main class="content">
            <section class="section" id="intro">
                <h2>১. ভেক্টরের ধারণা (Concept of Vector)</h2>
                <p class="lead">যে ভৌত রাশির সম্পূর্ণ বর্ণনার জন্য <b>মান (magnitude)</b> ও <b>দিক (direction)</b>—উভয়ই প্রয়োজন, তাকে <b>ভেক্টর রাশি (Vector quantity)</b> বলে। আর যে রাশির শুধু মান থাকলেই চলে, দিকের প্রয়োজন হয় না, তাকে <b>স্কেলার রাশি (Scalar quantity)</b> বলে।</p>
                <table>
                    <thead>
                        <tr>
                            <th>স্কেলার (Scalar)</th>
                            <th>ভেক্টর (Vector)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>শুধু মান দ্বারা প্রকাশ করা যায়</td>
                            <td>মান ও দিক উভয় দ্বারা প্রকাশ করতে হয়</td>
                        </tr>
                        <tr>
                            <td>সাধারণ বীজগাণিতিক নিয়মে যোগ করা যায়</td>
                            <td>ভেক্টর যোগের নিয়ম মানতে হয়</td>
                        </tr>
                        <tr>
                            <td>উদাহরণ: ভর, সময়, তাপমাত্রা, শক্তি, দূরত্ব, দ্রুতি</td>
                            <td>উদাহরণ: সরণ, বেগ, ত্বরণ, বল, ভরবেগ</td>
                        </tr>
                    </tbody>
                </table>
                <div class="callout"><b>মনে রাখবে:</b> দূরত্ব (distance) স্কেলার, কিন্তু সরণ (displacement) ভেক্টর। দ্রুতি (speed) স্কেলার, কিন্তু বেগ (velocity) ভেক্টর। একই মানের দুটি ভেক্টর ভিন্ন দিকে হলে তারা সমান ভেক্টর নয়।</div>
                <h3>ভেক্টরের চিত্রলিপি</h3>
                <p>সাধারণত \(\vec A\) দিয়ে ভেক্টর এবং \(A=|\vec A|\) বা \(|\vec A|\) দিয়ে তার মান বোঝানো হয়। চিত্রে তীরচিহ্নের দৈর্ঘ্য মানের সমানুপাতিক এবং তীরের মাথা দিক নির্দেশ করে।</p>
                <div class="eq">\[\vec A = A_x\hat i + A_y\hat j + A_z\hat k\]</div>
                <p>এখানে \(\hat i,\hat j,\hat k\) যথাক্রমে \(x,y,z\) অক্ষ বরাবর ধনাত্মক দিকের একক ভেক্টর (unit vectors)।</p>
            </section>

            <section class="section" id="types">
                <h2>২. ভেক্টরের প্রকারভেদ (Types of Vectors)</h2>
                <ul>
                    <li><b>শূন্য ভেক্টর (Zero vector):</b> যে ভেক্টরের মান শূন্য, \(\vec 0\)। এর নির্দিষ্ট দিক থাকে না।</li>
                    <li><b>একক ভেক্টর (Unit vector):</b> যে ভেক্টরের মান ১, যেমন \(\hat a=\vec A/|\vec A|\)। এটি দিক প্রকাশে কাজে লাগে।</li>
                    <li><b>সমান ভেক্টর (Equal vectors):</b> মান ও দিক উভয়ই সমান এমন ভেক্টর।</li>
                    <li><b>বিপরীত ভেক্টর (Negative/opposite vectors):</b> মান সমান কিন্তু দিক বিপরীত; \(\vec B=-\vec A\)।</li>
                    <li><b>সমান্তরাল/সমরেখ ভেক্টর (Parallel/collinear vectors):</b> একই বা সমান্তরাল রেখা বরাবর ক্রিয়াশীল ভেক্টর।</li>
                    <li><b>সমতলীয় ভেক্টর (Coplanar vectors):</b> একই সমতলে অবস্থিত ভেক্টরসমূহ।</li>
                    <li><b>অবস্থান ভেক্টর (Position vector):</b> মূলবিন্দু থেকে কোনো বিন্দুর অবস্থান নির্দেশকারী ভেক্টর।</li>
                    <li><b>সদৃশ ও বিসদৃশ ভেক্টর:</b> একই দিকে সমান্তরাল হলে সদৃশ; বিপরীত দিকে সমান্তরাল হলে বিসদৃশ বলা হয়।</li>
                </ul>
                <div class="eq">\[\text{একক ভেক্টর:}\qquad \hat A=\frac{\vec A}{|\vec A|},\qquad |\hat A|=1\]</div>
            </section>

            <section class="section" id="addition">
                <h2>৩. ভেক্টরের যোগ ও বিয়োগ (Addition and Subtraction)</h2>
                <h3>ত্রিভুজ সূত্র (Triangle law)</h3>
                <p>দুটি ভেক্টরকে মান ও দিক অপরিবর্তিত রেখে একটির শেষ প্রান্তে অন্যটির শুরু প্রান্ত বসালে, প্রথমটির শুরু থেকে দ্বিতীয়টির শেষ পর্যন্ত ভেক্টরটি তাদের লব্ধি (resultant)।</p>
                <div class="eq">\[\vec R=\vec A+\vec B\]</div>
                <p>যদি \(\vec A\) ও \(\vec B\)-এর মধ্যবর্তী কোণ \(\theta\) হয়, তবে লব্ধির মান:</p>
                <div class="eq">\[R=\sqrt{A^2+B^2+2AB\cos\theta}\]</div>
                <p>লব্ধি \(\vec A\)-এর সঙ্গে \(\alpha\) কোণ করলে:</p>
                <div class="eq">\[\tan\alpha=\frac{B\sin\theta}{A+B\cos\theta}\]</div>
                <h3>সামান্তরিক সূত্র (Parallelogram law)</h3>
                <p>দুটি ভেক্টরকে একই বিন্দু থেকে সামান্তরিকের দুটি সন্নিহিত বাহু দ্বারা প্রকাশ করলে, ঐ বিন্দু থেকে অঙ্কিত কর্ণ তাদের লব্ধি নির্দেশ করে।</p>
                <h3>বিশেষ অবস্থা</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ভেক্টরের মধ্যবর্তী কোণ</th>
                            <th>লব্ধির মান</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>\(\theta=0^\circ\), একই দিক</td>
                            <td>\(R=A+B\)</td>
                        </tr>
                        <tr>
                            <td>\(\theta=180^\circ\), বিপরীত দিক</td>
                            <td>\(R=|A-B|\)</td>
                        </tr>
                        <tr>
                            <td>\(\theta=90^\circ\), পরস্পর লম্ব</td>
                            <td>\(R=\sqrt{A^2+B^2}\)</td>
                        </tr>
                        <tr>
                            <td>\(A=B\), কোণ \(\theta\)</td>
                            <td>\(R=2A\cos(\theta/2)\)</td>
                        </tr>
                    </tbody>
                </table>
                <h3>ভেক্টর বিয়োগ (Subtraction)</h3>
                <p>একটি ভেক্টর বিয়োগ করা মানে তার বিপরীত ভেক্টর যোগ করা।</p>
                <div class="eq">\[\vec A-\vec B=\vec A+(-\vec B)\]</div>
            </section>

            <section class="section" id="components">
                <h2>৪. ভেক্টরের উপাংশ ও একক ভেক্টর (Components)</h2>
                <p>কোনো ভেক্টরকে নির্দিষ্ট অক্ষ বরাবর কয়েকটি ভেক্টরের যোগফল হিসেবে প্রকাশ করলে সেগুলোকে ওই ভেক্টরের উপাংশ (components) বলে। দ্বিমাত্রিক ক্ষেত্রে \(\vec A\), \(x\)-অক্ষের সঙ্গে \(\theta\) কোণ করলে:</p>
                <div class="eq">\[A_x=A\cos\theta,\qquad A_y=A\sin\theta\]</div>
                <div class="eq">\[\vec A=A_x\hat i+A_y\hat j,\qquad A=\sqrt{A_x^2+A_y^2}\]</div>
                <p>দিকের কোণ নির্ণয়:</p>
                <div class="eq">\[\tan\theta=\frac{A_y}{A_x}\]</div>
                <div class="callout warn"><b>সতর্কতা:</b> \(\tan^{-1}(A_y/A_x)\) থেকে কোণ বের করার সময় \(A_x\) ও \(A_y\)-এর চিহ্ন দেখে ভেক্টরটি কোন চতুর্ভাগে আছে তা নিশ্চিত করতে হবে।</div>
                <h3>ত্রিমাত্রিক ভেক্টর</h3>
                <div class="eq">\[\vec A=A_x\hat i+A_y\hat j+A_z\hat k\]</div>
                <div class="eq">\[A=\sqrt{A_x^2+A_y^2+A_z^2}\]</div>
                <p>দিক-অনুপাত বা direction cosines:</p>
                <div class="eq">\[l=\cos\alpha=\frac{A_x}{A},\quad m=\cos\beta=\frac{A_y}{A},\quad n=\cos\gamma=\frac{A_z}{A}\]</div>
                <div class="eq">\[l^2+m^2+n^2=1\]</div>
                <h3>ভেক্টর যোগের উপাংশ পদ্ধতি</h3>
                <p>যদি \(\vec R=\vec A+\vec B\), তাহলে</p>
                <div class="eq">\[R_x=A_x+B_x,\qquad R_y=A_y+B_y\]</div>
                <div class="eq">\[R=\sqrt{R_x^2+R_y^2},\qquad \tan\phi=\frac{R_y}{R_x}\]</div>
            </section>

            <section class="section" id="products">
                <h2>৫. ভেক্টরের গুণন (Products of Vectors)</h2>
                <h3>স্কেলার গুণন / ডট গুণন (Scalar or dot product)</h3>
                <p>দুটি ভেক্টরের মানের গুণফল এবং তাদের মধ্যবর্তী কোণের cosine-এর গুণফলকে ডট গুণন বলে। এর ফল একটি স্কেলার রাশি।</p>
                <div class="eq">\[\vec A\cdot\vec B=AB\cos\theta\]</div>
                <p>উপাংশে:</p>
                <div class="eq">\[\vec A\cdot\vec B=A_xB_x+A_yB_y+A_zB_z\]</div>
                <ul>
                    <li>একই দিকে হলে \(\vec A\cdot\vec B=AB\)।</li>
                    <li>লম্ব হলে \(\vec A\cdot\vec B=0\)।</li>
                    <li>বিপরীত দিকে হলে \(\vec A\cdot\vec B=-AB\)।</li>
                </ul>
                <p>উদাহরণ: ধ্রুব বলের কাজ \(W=\vec F\cdot\vec s=Fs\cos\theta\)।</p>
                <h3>ভেক্টর গুণন / ক্রস গুণন (Vector or cross product)</h3>
                <p>দুটি ভেক্টরের ক্রস গুণনের মান \(AB\sin\theta\), আর দিক উভয় ভেক্টরের সমতলের লম্ব। দিক ডান হাতের নিয়ম (right-hand rule) দিয়ে নির্ণয় করা হয়।</p>
                <div class="eq">\[|\vec A\times\vec B|=AB\sin\theta\]</div>
                <div class="eq">\[\vec A\times\vec B=(A_yB_z-A_zB_y)\hat i+(A_zB_x-A_xB_z)\hat j+(A_xB_y-A_yB_x)\hat k\]</div>
                <ul>
                    <li>সমান্তরাল হলে \(\vec A\times\vec B=\vec 0\)।</li>
                    <li>লম্ব হলে ক্রস গুণনের মান \(AB\), যা সর্বাধিক।</li>
                    <li>ক্রম অদলবদল করলে চিহ্ন বদলে যায়: \(\vec A\times\vec B=-(\vec B\times\vec A)\)।</li>
                </ul>
                <div class="eq">\[\hat i\cdot\hat i=\hat j\cdot\hat j=\hat k\cdot\hat k=1\]</div>
                <div class="eq">\[\hat i\cdot\hat j=\hat j\cdot\hat k=\hat k\cdot\hat i=0\]</div>
                <div class="eq">\[\hat i\times\hat j=\hat k,\quad \hat j\times\hat k=\hat i,\quad \hat k\times\hat i=\hat j\]</div>
                <div class="callout"><b>মনে রাখবে:</b> ডট গুণনের ফল স্কেলার; ক্রস গুণনের ফল ভেক্টর। ডট গুণনে \(\cos\theta\), ক্রস গুণনে \(\sin\theta\) থাকে।</div>
            </section>

            <section class="section" id="resolution">
                <h2>৬. লব্ধি নির্ণয় ও ব্যবহারিক ধারণা</h2>
                <h3>একাধিক ভেক্টরের লব্ধি</h3>
                <p>অনেকগুলো ভেক্টরের লব্ধি নির্ণয়ে প্রতিটি ভেক্টরের \(x\) ও \(y\) উপাংশ আলাদা করে যোগ করতে হয়।</p>
                <div class="eq">\[R_x=\sum A_x,\qquad R_y=\sum A_y\]</div>
                <div class="eq">\[R=\sqrt{\left(\sum A_x\right)^2+\left(\sum A_y\right)^2}\]</div>
                <p>লব্ধির দিক:</p>
                <div class="eq">\[\phi=\tan^{-1}\left(\frac{R_y}{R_x}\right)\quad\text{(চতুর্ভাগ যাচাই করতে হবে)}\]</div>
                <h3>সাম্যাবস্থা (Equilibrium)</h3>
                <p>কোনো বস্তুর ওপর ক্রিয়াশীল সব বলের ভেক্টর যোগফল শূন্য হলে বস্তুটি বলের সাম্যাবস্থায় আছে।</p>
                <div class="eq">\[\sum\vec F=\vec 0\quad\Rightarrow\quad \sum F_x=0,\ \sum F_y=0\]</div>
                <p>এক্ষেত্রে সাম্যকারী বল (equilibrant) লব্ধির সমান মানের কিন্তু বিপরীতমুখী:</p>
                <div class="eq">\[\vec E=-\vec R\]</div>
                <h3>নৌকা ও স্রোত / আপেক্ষিক বেগের ধারণা</h3>
                <p>বেগও ভেক্টর; তাই ভূমির সাপেক্ষে বেগ নির্ণয়ে ভেক্টর যোগ করতে হয়। যেমন, নৌকার জলের সাপেক্ষে বেগ \(\vec v_{b/w}\) এবং জলের ভূমির সাপেক্ষে বেগ \(\vec v_{w/g}\) হলে:</p>
                <div class="eq">\[\vec v_{b/g}=\vec v_{b/w}+\vec v_{w/g}\]</div>
                <p>এখানে সাবস্ক্রিপ্টের প্রথম অংশ বস্তু এবং দ্বিতীয় অংশ রেফারেন্সের নাম নির্দেশ করে।</p>
            </section>

            <section class="section" id="examples">
                <h2>৭. সমাধান করা উদাহরণ (Solved Examples)</h2>
                <div class="example">
                    <h3>উদাহরণ ১: দুটি লম্ব ভেক্টরের লব্ধি</h3>
                    <p>দুটি পরস্পর লম্ব বলের মান \(6\,\mathrm N\) ও \(8\,\mathrm N\)। লব্ধি কত?</p>
                    <p class="step"><b>সমাধান:</b> এখানে \(\theta=90^\circ\)।</p>
                    <div class="eq">\[R=\sqrt{A^2+B^2}=\sqrt{6^2+8^2}=\sqrt{100}=10\,\mathrm N\]</div>
                    <p><b>উত্তর:</b> \(10\,\mathrm N\)।</p>
                </div>
                <div class="example">
                    <h3>উদাহরণ ২: উপাংশ নির্ণয়</h3>
                    <p>একটি \(20\,\mathrm N\) বল ধনাত্মক \(x\)-অক্ষের সঙ্গে \(30^\circ\) কোণ করে। উপাংশ দুটি নির্ণয় করো।</p>
                    <div class="eq">\[F_x=F\cos30^\circ=20\left(\frac{\sqrt3}{2}\right)=10\sqrt3\approx17.32\,\mathrm N\]</div>
                    <div class="eq">\[F_y=F\sin30^\circ=20\left(\frac12\right)=10\,\mathrm N\]</div>
                    <p><b>উত্তর:</b> \(F_x\approx17.32\,\mathrm N\), \(F_y=10\,\mathrm N\)।</p>
                </div>
                <div class="example">
                    <h3>উদাহরণ ৩: বিপরীতমুখী ভেক্টর</h3>
                    <p>পূর্বদিকে \(15\,\mathrm m\) এবং পশ্চিমদিকে \(9\,\mathrm m\) সরণ ঘটলে লব্ধি সরণ কত?</p>
                    <div class="eq">\[R=15-9=6\,\mathrm m\]</div>
                    <p><b>উত্তর:</b> \(6\,\mathrm m\) পূর্বদিকে।</p>
                </div>
                <div class="example">
                    <h3>উদাহরণ ৪: ডট গুণন</h3>
                    <p>\(|\vec A|=5\), \(|\vec B|=4\), এবং তাদের মধ্যবর্তী কোণ \(60^\circ\)। \(\vec A\cdot\vec B\) নির্ণয় করো।</p>
                    <div class="eq">\[\vec A\cdot\vec B=AB\cos60^\circ=5\times4\times\frac12=10\]</div>
                    <p><b>উত্তর:</b> \(10\) (একক প্রদত্ত রাশির এককের গুণফলের ওপর নির্ভরশীল)।</p>
                </div>
                <div class="example">
                    <h3>উদাহরণ ৫: উপাংশ দিয়ে লব্ধি</h3>
                    <p>\(\vec A=3\hat i+4\hat j\) এবং \(\vec B=2\hat i-\hat j\)। লব্ধির মান নির্ণয় করো।</p>
                    <div class="eq">\[\vec R=\vec A+\vec B=(3+2)\hat i+(4-1)\hat j=5\hat i+3\hat j\]</div>
                    <div class="eq">\[R=\sqrt{5^2+3^2}=\sqrt{34}\approx5.83\]</div>
                    <p><b>উত্তর:</b> \(\vec R=5\hat i+3\hat j\), মান \(\sqrt{34}\)।</p>
                </div>
                <div class="example">
                    <h3>উদাহরণ ৬: ক্রস গুণনের মান</h3>
                    <p>\(A=3\), \(B=7\), মধ্যবর্তী কোণ \(90^\circ\)। ক্রস গুণনের মান কত?</p>
                    <div class="eq">\[|\vec A\times\vec B|=AB\sin90^\circ=3\times7=21\]</div>
                    <p><b>উত্তর:</b> \(21\) (এককের গুণফল)।</p>
                </div>
            </section>

            <section class="section" id="formula">
                <h2>৮. সূত্রসংকলন (Formula Sheet)</h2>
                <ul class="formula-list">
                    <li><b>ভেক্টরের মান:</b> \(A=\sqrt{A_x^2+A_y^2}\)</li>
                    <li><b>উপাংশ:</b> \(A_x=A\cos\theta,\ A_y=A\sin\theta\)</li>
                    <li><b>দুটি ভেক্টরের লব্ধি:</b> \(R=\sqrt{A^2+B^2+2AB\cos\theta}\)</li>
                    <li><b>লব্ধির দিক:</b> \(\tan\alpha=\dfrac{B\sin\theta}{A+B\cos\theta}\)</li>
                    <li><b>লম্ব ভেক্টরের লব্ধি:</b> \(R=\sqrt{A^2+B^2}\)</li>
                    <li><b>ত্রিমাত্রিক মান:</b> \(A=\sqrt{A_x^2+A_y^2+A_z^2}\)</li>
                    <li><b>ডট গুণন:</b> \(\vec A\cdot\vec B=AB\cos\theta\)</li>
                    <li><b>ক্রস গুণনের মান:</b> \(|\vec A\times\vec B|=AB\sin\theta\)</li>
                    <li><b>সাম্যাবস্থা:</b> \(\sum\vec F=\vec0\)</li>
                    <li><b>একক ভেক্টর:</b> \(\hat A=\vec A/A\)</li>
                    <li><b>দিক কোসাইন:</b> \(l^2+m^2+n^2=1\)</li>
                </ul>
            </section>

            <section class="section" id="practice">
                <h2>৯. অনুশীলনী (Practice Questions)</h2>
                <ol>
                    <li>স্কেলার ও ভেক্টর রাশির সংজ্ঞা দাও এবং প্রত্যেকটির চারটি উদাহরণ লেখো।</li>
                    <li>ত্রিভুজ সূত্র ব্যবহার করে ভেক্টর যোগ ব্যাখ্যা করো।</li>
                    <li>দুটি ভেক্টরের মধ্যবর্তী কোণ \(120^\circ\), মান যথাক্রমে \(5\) ও \(8\)। লব্ধির মান নির্ণয় করো।</li>
                    <li>একটি \(50\,\mathrm N\) বল অনুভূমিকের সঙ্গে \(37^\circ\) কোণ করে। এর অনুভূমিক ও উল্লম্ব উপাংশ নির্ণয় করো। \((\sin37^\circ\approx0.6,\ \cos37^\circ\approx0.8)\)</li>
                    <li>\(\vec A=2\hat i-3\hat j\) এবং \(\vec B=4\hat i+5\hat j\)। \(\vec A+\vec B\) ও তার মান নির্ণয় করো।</li>
                    <li>ডট গুণন ও ক্রস গুণনের মধ্যে তিনটি পার্থক্য লেখো।</li>
                    <li>দুটি বলের লব্ধি শূন্য হওয়ার জন্য তাদের মান ও দিকের সম্পর্ক কী?</li>
                    <li>সাম্যকারী বল কাকে বলে? লব্ধি বলের সঙ্গে এর সম্পর্ক লেখো।</li>
                    <li>দিক কোসাইনের সম্পর্ক \(l^2+m^2+n^2=1\) ব্যাখ্যা করো।</li>
                    <li>নৌকার বেগ ও স্রোতের বেগ দিয়ে ভূমির সাপেক্ষে নৌকার বেগ কীভাবে নির্ণয় করা হয়?</li>
                </ol>
                <div class="callout tip"><b>পরীক্ষার প্রস্তুতি:</b> সংজ্ঞা ও পার্থক্য মুখস্থ করার পাশাপাশি লব্ধি, উপাংশ, ডট গুণন এবং ক্রস গুণনের অঙ্ক বারবার অনুশীলন করবে। চিত্রভিত্তিক প্রশ্নে তীরের দিক এবং কোণ পরিষ্কারভাবে দেখাবে।</div>
            </section>

            <section class="section" id="quiz">
                <h2>১০. MCQ পরীক্ষা (Interactive Quiz)</h2>
                <p>প্রতিটি প্রশ্নের একটি সঠিক উত্তর নির্বাচন করে “উত্তর যাচাই” চাপো।</p>
                <div class="quiz" data-answer="b">
                    <p>১. নিচের কোনটি ভেক্টর রাশি?</p><label><input type="radio" name="q1" value="a"> শক্তি (Energy)</label><label><input type="radio" name="q1" value="b"> ত্বরণ (Acceleration)</label><label><input type="radio" name="q1" value="c"> সময় (Time)</label><label><input type="radio" name="q1" value="d"> তাপমাত্রা (Temperature)</label><button type="button" onclick="checkQuiz(this)">উত্তর যাচাই</button>
                    <div class="feedback" aria-live="polite"></div>
                </div>
                <div class="quiz" data-answer="c">
                    <p>২. দুটি লম্ব ভেক্টরের মান \(3\) ও \(4\)। লব্ধির মান কত?</p><label><input type="radio" name="q2" value="a"> ১</label><label><input type="radio" name="q2" value="b"> ৭</label><label><input type="radio" name="q2" value="c"> ৫</label><label><input type="radio" name="q2" value="d"> ১২</label><button type="button" onclick="checkQuiz(this)">উত্তর যাচাই</button>
                    <div class="feedback" aria-live="polite"></div>
                </div>
                <div class="quiz" data-answer="a">
                    <p>৩. দুটি ভেক্টর পরস্পর লম্ব হলে তাদের ডট গুণন কত?</p><label><input type="radio" name="q3" value="a"> শূন্য</label><label><input type="radio" name="q3" value="b"> \(AB\)</label><label><input type="radio" name="q3" value="c"> \(A+B\)</label><label><input type="radio" name="q3" value="d"> ১</label><button type="button" onclick="checkQuiz(this)">উত্তর যাচাই</button>
                    <div class="feedback" aria-live="polite"></div>
                </div>
                <div class="quiz" data-answer="d">
                    <p>৪. \(\vec A\times\vec B\)-এর ফল কী ধরনের রাশি?</p><label><input type="radio" name="q4" value="a"> স্কেলার</label><label><input type="radio" name="q4" value="b"> মাত্রাহীন স্কেলার</label><label><input type="radio" name="q4" value="c"> সবসময় শূন্য</label><label><input type="radio" name="q4" value="d"> ভেক্টর</label><button type="button" onclick="checkQuiz(this)">উত্তর যাচাই</button>
                    <div class="feedback" aria-live="polite"></div>
                </div>
                <div class="quiz" data-answer="b">
                    <p>৫. \(\vec A=3\hat i+4\hat j\) হলে \(|\vec A|\) কত?</p><label><input type="radio" name="q5" value="a"> ৭</label><label><input type="radio" name="q5" value="b"> ৫</label><label><input type="radio" name="q5" value="c"> ১</label><label><input type="radio" name="q5" value="d"> ১২</label><button type="button" onclick="checkQuiz(this)">উত্তর যাচাই</button>
                    <div class="feedback" aria-live="polite"></div>
                </div>
            </section>
            <div class="callout"><b>শিক্ষক-নোট:</b> এই পৃষ্ঠাটি HSC Physics 1st Paper-এর “ভেক্টর” অধ্যায়ের পাঠ-সহায়িকা হিসেবে তৈরি। শ্রেণিতে ব্যবহারের আগে সর্বশেষ NCTB পাঠ্যবই, সিলেবাস ও বোর্ডের নির্দেশনার সঙ্গে বিষয়বস্তু মিলিয়ে নেবে।</div>
        </main>
    </div>
    <footer>© <?= $currentYear ?> • HSC Physics 1st Paper • Chapter 2: Vector</footer>
    <script>
        function checkQuiz(button) {
            const quiz = button.closest('.quiz');
            const selected = quiz.querySelector('input[type="radio"]:checked');
            const feedback = quiz.querySelector('.feedback');
            if (!selected) {
                feedback.textContent = 'আগে একটি উত্তর নির্বাচন করো।';
                feedback.className = 'feedback incorrect';
                return;
            }
            if (selected.value === quiz.dataset.answer) {
                feedback.textContent = 'সঠিক উত্তর! খুব ভালো।';
                feedback.className = 'feedback correct';
            } else {
                feedback.textContent = 'এটি সঠিক নয়। আবার চেষ্টা করো এবং সূত্রটি দেখে নাও।';
                feedback.className = 'feedback incorrect';
            }
        }
    </script>
</body>

</html>