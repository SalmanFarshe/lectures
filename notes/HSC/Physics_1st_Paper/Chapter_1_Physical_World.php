<?php
/*
 * HSC Physics 1st Paper — Chapter 1: Physical World and Measurement
 * Save as index.php and run on a PHP-enabled server (e.g. XAMPP/WAMP).
 */
$pageTitle = "HSC Physics 1st Paper | অধ্যায় ১: ভৌত জগৎ ও পরিমাপ";
$year = date("Y");
?>
<!doctype html>
<html lang="bn">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="HSC Physics 1st Paper Chapter 1 lecture notes: Physical World and Measurement.">
    <!-- MathJax renders equations in proper textbook-style mathematical typography. -->
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
                ],
                processEscapes: true
            },
            options: {
                skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code']
            },
            chtml: {
                scale: 1.05
            }
        };
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml.js"></script>
    <style>
        :root {
            --ink: #172033;
            --muted: #596579;
            --paper: #ffffff;
            --page: #f3f6fb;
            --line: #dce3ee;
            --brand: #164e8a;
            --brand-soft: #eaf3ff;
            --accent: #087f8c;
            --note: #fff8df;
            --note-line: #e6c65a;
            --shadow: 0 8px 28px rgba(22, 42, 74, .08);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            background: var(--page);
            color: var(--ink);
            font-family: "Noto Sans Bengali", "Hind Siliguri", "SolaimanLipi", system-ui, sans-serif;
            line-height: 1.8;
        }

        a {
            color: var(--brand);
        }

        .hero {
            color: #fff;
            background: linear-gradient(130deg, #123d70, #087f8c);
            padding: 42px 20px 34px;
        }

        .hero-inner,
        .layout,
        footer {
            max-width: 1120px;
            margin: 0 auto;
        }

        .eyebrow {
            font-size: .88rem;
            letter-spacing: .04em;
            opacity: .9;
        }

        h1 {
            margin: 8px 0;
            line-height: 1.35;
            font-size: clamp(1.7rem, 4vw, 2.6rem);
        }

        .hero p {
            margin: 8px 0 0;
            opacity: .95;
        }

        .layout {
            display: grid;
            grid-template-columns: 255px minmax(0, 1fr);
            gap: 22px;
            padding: 24px 16px 48px;
            align-items: start;
        }

        aside,
        main {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: var(--shadow);
        }

        aside {
            padding: 18px;
            position: sticky;
            top: 14px;
            max-height: calc(100vh - 28px);
            overflow: auto;
        }

        aside h2 {
            margin: 0 0 10px;
            font-size: 1.05rem;
        }

        aside a {
            display: block;
            text-decoration: none;
            padding: 5px 0;
            font-size: .9rem;
        }

        aside a:hover {
            text-decoration: underline;
        }

        main {
            padding: clamp(18px, 3vw, 36px);
            min-width: 0;
        }

        section {
            padding-bottom: 22px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--line);
        }

        section:last-child {
            border-bottom: 0;
            margin-bottom: 0;
        }

        h2 {
            color: var(--brand);
            font-size: 1.45rem;
            line-height: 1.45;
            margin: 0 0 14px;
        }

        h3 {
            margin: 22px 0 8px;
            font-size: 1.12rem;
            color: #173b63;
        }

        p {
            margin: 8px 0 12px;
        }

        ul,
        ol {
            padding-left: 1.5rem;
        }

        li {
            margin: 4px 0;
        }

        .lead {
            font-size: 1.04rem;
        }

        .callout {
            background: var(--brand-soft);
            border-left: 4px solid var(--brand);
            border-radius: 8px;
            padding: 12px 16px;
            margin: 14px 0;
        }

        .warning {
            background: var(--note);
            border-left: 4px solid var(--note-line);
            border-radius: 8px;
            padding: 12px 16px;
            margin: 14px 0;
        }

        .formula {
            background: #fffdf7;
            border: 1px solid #e5dcc6;
            border-left: 4px solid #a98242;
            border-radius: 3px;
            padding: 13px 16px;
            margin: 13px 0;
            text-align: center;
            font-family: "Cambria Math", "STIX Two Math", "Times New Roman", serif;
            font-size: 1.13rem;
            line-height: 1.9;
            overflow-x: auto;
            color: #172033;
        }

        .formula mjx-container {
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
        }

        mjx-container[display="true"] {
            margin: .25em 0 !important;
        }

        @media print {
            .formula {
                background: #fff;
                border-color: #bbb;
                break-inside: avoid;
            }
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 18px;
            font-size: .95rem;
        }

        th,
        td {
            border: 1px solid var(--line);
            padding: 9px 11px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #edf3fa;
            color: #183a60;
        }

        tr:nth-child(even) td {
            background: #fafcff;
        }

        .grid-cards {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .mini-card {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 14px;
            background: #fff;
        }

        .mini-card h3 {
            margin-top: 0;
        }

        .tag {
            display: inline-block;
            background: #e7f6f5;
            color: #075d65;
            border-radius: 999px;
            padding: 2px 10px;
            font-size: .8rem;
        }

        .question {
            padding: 14px 0;
            border-bottom: 1px dashed var(--line);
        }

        .question:last-child {
            border-bottom: 0;
        }

        .question button {
            margin: 5px 7px 4px 0;
            border: 1px solid #c6d2e2;
            background: #fff;
            border-radius: 8px;
            padding: 7px 10px;
            cursor: pointer;
            font: inherit;
        }

        .question button:hover {
            background: var(--brand-soft);
        }

        .feedback {
            font-weight: 700;
            margin-top: 6px;
        }

        .correct {
            color: #087443;
        }

        .incorrect {
            color: #b42318;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 16px 0;
        }

        .actions button {
            border: 0;
            border-radius: 8px;
            padding: 10px 14px;
            cursor: pointer;
            background: var(--brand);
            color: white;
            font: inherit;
        }

        .actions button.secondary {
            background: #e7edf5;
            color: var(--ink);
        }

        footer {
            padding: 0 16px 28px;
            color: var(--muted);
            font-size: .9rem;
        }

        @media (max-width: 820px) {
            .layout {
                grid-template-columns: 1fr;
            }

            aside {
                position: static;
                max-height: none;
            }

            .grid-cards {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            body {
                background: white;
            }

            .hero {
                background: white;
                color: black;
                padding: 10px 0;
            }

            .layout {
                display: block;
                padding: 0;
            }

            aside,
            .actions,
            .question button {
                display: none !important;
            }

            main {
                border: 0;
                box-shadow: none;
                padding: 0;
            }

            section {
                break-inside: avoid;
            }

            a {
                color: black;
                text-decoration: none;
            }
        }
    </style>
</head>

<body>
    <header class="hero">
        <div class="hero-inner">
            <div class="eyebrow">HSC Physics 1st Paper · NCTB-aligned study notes</div>
            <h1>অধ্যায় ১: ভৌত জগৎ ও পরিমাপ</h1>
            <p>Physical World and Measurement — বিস্তারিত ক্লাস লেকচার</p>
        </div>
    </header>

    <div class="layout">
        <aside aria-label="সূচিপত্র">
            <h2>সূচিপত্র</h2>
            <a href="#outcomes">শিখনফল</a>
            <a href="#intro">১.১ পদার্থবিজ্ঞানের পরিচয়</a>
            <a href="#evolution">১.২ ক্রমবিকাশ</a>
            <a href="#measurement">১.৩ ভৌত রাশি ও পরিমাপ</a>
            <a href="#classification">১.৪ রাশির শ্রেণিবিভাগ</a>
            <a href="#si">১.৫ SI একক পদ্ধতি</a>
            <a href="#dimension">১.৬ মাত্রা ও মাত্রা সমীকরণ</a>
            <a href="#dimensional-principle">১.৭ মাত্রার নীতি</a>
            <a href="#dimensional-analysis">১.৮ মাত্রা বিশ্লেষণ</a>
            <a href="#errors">১.৯ পরিমাপের ত্রুটি</a>
            <a href="#error-math">১.১০ ত্রুটির হিসাব</a>
            <a href="#accuracy">১.১১ Accuracy ও Precision</a>
            <a href="#significant">১.১২ তাৎপর্যপূর্ণ অঙ্ক</a>
            <a href="#instruments">১.১৩ পরিমাপক যন্ত্র</a>
            <a href="#improve">১.১৪ পরিমাপের মান উন্নয়ন</a>
            <a href="#formula-sheet">সূত্র সংকলন</a>
            <a href="#practice">অনুশীলনী</a>
        </aside>

        <main>
            <section id="outcomes">
                <h2>শিখনফল (Learning Outcomes)</h2>
                <p class="lead">এই অধ্যায় শেষে শিক্ষার্থীরা—</p>
                <ol>
                    <li>পদার্থবিজ্ঞানের প্রকৃতি, পরিধি ও অন্যান্য বিজ্ঞানের সঙ্গে সম্পর্ক ব্যাখ্যা করতে পারবে।</li>
                    <li>ভৌত রাশি (Physical Quantity), একক (Unit) ও পরিমাপের ধারণা বুঝতে পারবে।</li>
                    <li>মৌলিক ও লব্ধ রাশি এবং SI একক পদ্ধতি ব্যাখ্যা করতে পারবে।</li>
                    <li>মাত্রা (Dimension), মাত্রা সমীকরণ (Dimensional Equation) ও মাত্রার নীতি প্রয়োগ করতে পারবে।</li>
                    <li>পরিমাপের ত্রুটি (Error), Accuracy, Precision ও Significant Figures বুঝতে পারবে।</li>
                    <li>একক রূপান্তর, মাত্রা যাচাই এবং পরিমাপসংক্রান্ত গাণিতিক সমস্যা সমাধান করতে পারবে।</li>
                </ol>
            </section>

            <section id="intro">
                <h2>১.১ পদার্থবিজ্ঞানের পরিচয় (Introduction to Physics)</h2>
                <p><strong>পদার্থবিজ্ঞান (Physics)</strong> হলো বিজ্ঞানের সেই শাখা যেখানে পদার্থ ও শক্তির ধর্ম, তাদের পারস্পরিক ক্রিয়া এবং স্থান ও সময়ের সঙ্গে তাদের পরিবর্তন নিয়ে আলোচনা করা হয়। পর্যবেক্ষণ, পরীক্ষা-নিরীক্ষা ও গাণিতিক বিশ্লেষণের সাহায্যে প্রাকৃতিক ঘটনাগুলোর ব্যাখ্যা দেওয়া এর মূল উদ্দেশ্য।</p>
                <h3>পদার্থবিজ্ঞানের পরিধি (Scope of Physics)</h3>
                <div class="grid-cards">
                    <div class="mini-card">
                        <h3>১. জ্যোতির্বিজ্ঞান (Astronomy)</h3>
                        <p>নক্ষত্র, গ্রহ, ছায়াপথ, মহাকর্ষ ও মহাবিশ্বের গঠন নিয়ে আলোচনা।</p>
                    </div>
                    <div class="mini-card">
                        <h3>২. বলবিদ্যা (Mechanics)</h3>
                        <p>বস্তুর গতি, বল, কাজ, শক্তি ও ভরবেগের সম্পর্ক নিয়ে আলোচনা।</p>
                    </div>
                    <div class="mini-card">
                        <h3>৩. তড়িৎ ও চুম্বকত্ব</h3>
                        <p>Electricity and Magnetism: চার্জ, তড়িৎ প্রবাহ, চৌম্বক ক্ষেত্র ও তড়িৎচুম্বকীয় তরঙ্গ।</p>
                    </div>
                    <div class="mini-card">
                        <h3>৪. আলোকবিজ্ঞান (Optics)</h3>
                        <p>আলোর প্রতিফলন, প্রতিসরণ, ব্যতিচার ও অপবর্তন নিয়ে আলোচনা।</p>
                    </div>
                    <div class="mini-card">
                        <h3>৫. আধুনিক পদার্থবিজ্ঞান</h3>
                        <p>পরমাণু, নিউক্লিয়াস, কোয়ান্টাম তত্ত্ব, আপেক্ষিকতা ও মৌলিক কণার ধর্ম।</p>
                    </div>
                </div>
                <h3>অন্যান্য বিজ্ঞানের সঙ্গে সম্পর্ক</h3>
                <ul>
                    <li><strong>রসায়ন (Chemistry):</strong> পরমাণুর গঠন, রাসায়নিক বন্ধন ও অণুর শক্তি ব্যাখ্যায় পদার্থবিজ্ঞান গুরুত্বপূর্ণ।</li>
                    <li><strong>জীববিজ্ঞান (Biology):</strong> দৃষ্টিশক্তি, শ্রবণ, চিকিৎসা-চিত্রায়ণ ও জীবদেহের বিভিন্ন প্রক্রিয়া বুঝতে সহায়তা করে।</li>
                    <li><strong>প্রকৌশল (Engineering):</strong> যন্ত্র, সেতু, বৈদ্যুতিক ব্যবস্থা ও যোগাযোগপ্রযুক্তির নকশায় ব্যবহৃত হয়।</li>
                    <li><strong>জ্যোতির্বিজ্ঞান (Astronomy):</strong> মহাজাগতিক বস্তুর গতি, আলো ও মহাকর্ষ বিশ্লেষণে প্রয়োজন।</li>
                </ul>
                <div class="callout"><strong>মনে রাখবে:</strong> পদার্থবিজ্ঞান শুধু সূত্র মুখস্থ করার বিষয় নয়; পর্যবেক্ষণ, যুক্তি, পরীক্ষা এবং গাণিতিক বিশ্লেষণের মাধ্যমে প্রকৃতিকে বোঝার বিজ্ঞান।</div>
            </section>

            <section id="evolution">
                <h2>১.২ পদার্থবিজ্ঞানের ক্রমবিকাশ (Evolution of Physics)</h2>
                <p>পর্যবেক্ষণ, পরীক্ষা-নিরীক্ষা ও গাণিতিক বিশ্লেষণের মাধ্যমে বিভিন্ন যুগে পদার্থবিজ্ঞানের বিকাশ ঘটেছে।</p>
                <table>
                    <thead>
                        <tr>
                            <th>বিজ্ঞানী</th>
                            <th>প্রধান অবদান</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>গ্যালিলিও গ্যালিলি (Galileo Galilei)</td>
                            <td>পরীক্ষানির্ভর পদার্থবিজ্ঞানের বিকাশ এবং গতির অধ্যয়ন।</td>
                        </tr>
                        <tr>
                            <td>আইজ্যাক নিউটন (Isaac Newton)</td>
                            <td>গতির তিনটি সূত্র ও সার্বজনীন মহাকর্ষ সূত্র।</td>
                        </tr>
                        <tr>
                            <td>মাইকেল ফ্যারাডে (Michael Faraday)</td>
                            <td>তড়িৎচুম্বকীয় আবেশ ও তড়িৎচুম্বকত্বের বিকাশ।</td>
                        </tr>
                        <tr>
                            <td>জেমস ক্লার্ক ম্যাক্সওয়েল (James Clerk Maxwell)</td>
                            <td>তড়িৎচুম্বকীয় ক্ষেত্রের গাণিতিক তত্ত্ব।</td>
                        </tr>
                        <tr>
                            <td>ম্যাক্স প্ল্যাঙ্ক (Max Planck)</td>
                            <td>কোয়ান্টাম তত্ত্বের সূচনা।</td>
                        </tr>
                        <tr>
                            <td>আলবার্ট আইনস্টাইন (Albert Einstein)</td>
                            <td>বিশেষ ও সাধারণ আপেক্ষিকতা তত্ত্ব; আলোক-তড়িৎ ক্রিয়ার ব্যাখ্যা।</td>
                        </tr>
                    </tbody>
                </table>
                <h3>বৈজ্ঞানিক ধারণার পার্থক্য</h3>
                <ul>
                    <li><strong>অনুকল্প (Hypothesis):</strong> কোনো ঘটনা ব্যাখ্যা করার জন্য প্রস্তাবিত পরীক্ষাযোগ্য ধারণা।</li>
                    <li><strong>বৈজ্ঞানিক সূত্র (Scientific Law):</strong> প্রকৃতির নিয়মিত সম্পর্ককে সংক্ষিপ্তভাবে প্রকাশ করে।</li>
                    <li><strong>বৈজ্ঞানিক তত্ত্ব (Scientific Theory):</strong> পর্যবেক্ষণ ও পরীক্ষার সমর্থনপ্রাপ্ত বিস্তৃত ব্যাখ্যামূলক কাঠামো।</li>
                    <li><strong>মডেল (Model):</strong> কোনো জটিল ব্যবস্থা বা ঘটনাকে সহজভাবে বোঝানোর উপস্থাপন।</li>
                </ul>
            </section>

            <section id="measurement">
                <h2>১.৩ ভৌত রাশি ও পরিমাপ (Physical Quantity and Measurement)</h2>
                <h3>ভৌত রাশি (Physical Quantity)</h3>
                <p>যে রাশি পরিমাপ করা যায় এবং সংখ্যামান ও এককের সাহায্যে প্রকাশ করা যায়, তাকে ভৌত রাশি বলে। যেমন: দৈর্ঘ্য, ভর, সময়, তাপমাত্রা, বেগ, বল, কাজ ও শক্তি।</p>
                <div class="formula">Q = n × u</div>
                <ul>
                    <li><strong>Q</strong> = ভৌত রাশির মান</li>
                    <li><strong>n</strong> = সংখ্যামান (Numerical Value)</li>
                    <li><strong>u</strong> = একক (Unit)</li>
                </ul>
                <p>উদাহরণ: কোনো টেবিলের দৈর্ঘ্য 2.5 m হলে, 2.5 হলো সংখ্যামান এবং m হলো একক।</p>
                <h3>পরিমাপ (Measurement)</h3>
                <p>কোনো ভৌত রাশির অজানা মানকে একই প্রকৃতির একটি নির্দিষ্ট মানের সঙ্গে তুলনা করে নির্ণয় করার প্রক্রিয়াকে পরিমাপ বলে।</p>
                <h3>একক (Unit)</h3>
                <p>কোনো ভৌত রাশি পরিমাপের জন্য গৃহীত একই প্রকৃতির একটি নির্দিষ্ট আদর্শ পরিমাণকে ওই রাশির একক বলে। যেমন: দৈর্ঘ্যের মিটার (m), ভরের কিলোগ্রাম (kg), সময়ের সেকেন্ড (s)।</p>
                <h3>একক পরিবর্তন</h3>
                <p>একই ভৌত রাশির একক পরিবর্তন হলেও প্রকৃত মান পরিবর্তিত হয় না। যেমন, 2 m = 200 cm।</p>
                <div class="formula">n₁u₁ = n₂u₂ &nbsp; ⇒ &nbsp; n₂ = n₁(u₁/u₂)</div>
            </section>

            <section id="classification">
                <h2>১.৪ ভৌত রাশির শ্রেণিবিভাগ (Classification)</h2>
                <div class="grid-cards">
                    <div class="mini-card">
                        <h3>মৌলিক রাশি (Fundamental Quantities)</h3>
                        <p>যেসব ভৌত রাশি অন্য কোনো ভৌত রাশির সাহায্য ছাড়া স্বাধীনভাবে সংজ্ঞায়িত করা হয়। যেমন: দৈর্ঘ্য, ভর ও সময়।</p>
                    </div>
                    <div class="mini-card">
                        <h3>লব্ধ রাশি (Derived Quantities)</h3>
                        <p>যেসব ভৌত রাশি এক বা একাধিক মৌলিক রাশির গাণিতিক সমন্বয়ে প্রকাশ করা যায়। যেমন: বেগ, ত্বরণ, বল ও কাজ।</p>
                    </div>
                </div>
                <p>উদাহরণস্বরূপ, বেগ হলো সরণ ও সময়ের অনুপাত:</p>
                <div class="formula">v = s/t</div>
                <p>বেগের SI একক m s<sup>−1</sup>।</p>
            </section>

            <section id="si">
                <h2>১.৫ আন্তর্জাতিক একক পদ্ধতি (SI)</h2>
                <p>SI হলো আন্তর্জাতিকভাবে স্বীকৃত একক পদ্ধতি। এতে সাতটি মৌলিক রাশি ও সাতটি মৌলিক একক রয়েছে।</p>
                <table>
                    <thead>
                        <tr>
                            <th>ক্রম</th>
                            <th>মৌলিক রাশি</th>
                            <th>English name</th>
                            <th>SI একক</th>
                            <th>প্রতীক</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>১</td>
                            <td>দৈর্ঘ্য</td>
                            <td>Length</td>
                            <td>মিটার</td>
                            <td>m</td>
                        </tr>
                        <tr>
                            <td>২</td>
                            <td>ভর</td>
                            <td>Mass</td>
                            <td>কিলোগ্রাম</td>
                            <td>kg</td>
                        </tr>
                        <tr>
                            <td>৩</td>
                            <td>সময়</td>
                            <td>Time</td>
                            <td>সেকেন্ড</td>
                            <td>s</td>
                        </tr>
                        <tr>
                            <td>৪</td>
                            <td>তড়িৎ প্রবাহ</td>
                            <td>Electric current</td>
                            <td>অ্যাম্পিয়ার</td>
                            <td>A</td>
                        </tr>
                        <tr>
                            <td>৫</td>
                            <td>তাপগতীয় তাপমাত্রা</td>
                            <td>Thermodynamic temperature</td>
                            <td>কেলভিন</td>
                            <td>K</td>
                        </tr>
                        <tr>
                            <td>৬</td>
                            <td>পদার্থের পরিমাণ</td>
                            <td>Amount of substance</td>
                            <td>মোল</td>
                            <td>mol</td>
                        </tr>
                        <tr>
                            <td>৭</td>
                            <td>দীপন তীব্রতা</td>
                            <td>Luminous intensity</td>
                            <td>ক্যান্ডেলা</td>
                            <td>cd</td>
                        </tr>
                    </tbody>
                </table>
                <div class="warning"><strong>পরীক্ষায় মনে রাখবে:</strong> SI এককের প্রতীক লেখার সময় বড় ও ছোট হাতের অক্ষরের ব্যবহার গুরুত্বপূর্ণ। যেমন kg, K, A এবং s।</div>
                <h3>কয়েকটি গুরুত্বপূর্ণ লব্ধ একক</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ভৌত রাশি</th>
                            <th>সম্পর্ক</th>
                            <th>SI একক</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>ক্ষেত্রফল</td>
                            <td>দৈর্ঘ্য × দৈর্ঘ্য</td>
                            <td>m²</td>
                        </tr>
                        <tr>
                            <td>আয়তন</td>
                            <td>দৈর্ঘ্য³</td>
                            <td>m³</td>
                        </tr>
                        <tr>
                            <td>বেগ</td>
                            <td>সরণ/সময়</td>
                            <td>m s⁻¹</td>
                        </tr>
                        <tr>
                            <td>ত্বরণ</td>
                            <td>বেগের পরিবর্তন/সময়</td>
                            <td>m s⁻²</td>
                        </tr>
                        <tr>
                            <td>বল</td>
                            <td>ভর × ত্বরণ</td>
                            <td>নিউটন (N)</td>
                        </tr>
                        <tr>
                            <td>কাজ</td>
                            <td>বল × সরণ</td>
                            <td>জুল (J)</td>
                        </tr>
                        <tr>
                            <td>ক্ষমতা</td>
                            <td>কাজ/সময়</td>
                            <td>ওয়াট (W)</td>
                        </tr>
                        <tr>
                            <td>চাপ</td>
                            <td>বল/ক্ষেত্রফল</td>
                            <td>প্যাসকেল (Pa)</td>
                        </tr>
                        <tr>
                            <td>ঘনত্ব</td>
                            <td>ভর/আয়তন</td>
                            <td>kg m⁻³</td>
                        </tr>
                    </tbody>
                </table>
                <h3>প্রচলিত একক পদ্ধতি</h3>
                <table>
                    <thead>
                        <tr>
                            <th>পদ্ধতি</th>
                            <th>দৈর্ঘ্য</th>
                            <th>ভর</th>
                            <th>সময়</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>CGS</td>
                            <td>সেন্টিমিটার (cm)</td>
                            <td>গ্রাম (g)</td>
                            <td>সেকেন্ড (s)</td>
                        </tr>
                        <tr>
                            <td>MKS</td>
                            <td>মিটার (m)</td>
                            <td>কিলোগ্রাম (kg)</td>
                            <td>সেকেন্ড (s)</td>
                        </tr>
                        <tr>
                            <td>FPS</td>
                            <td>ফুট (ft)</td>
                            <td>পাউন্ড (lb)</td>
                            <td>সেকেন্ড (s)</td>
                        </tr>
                    </tbody>
                </table>
                <h3>একক রূপান্তর</h3>
                <div class="formula">1 km = 10³ m &nbsp; | &nbsp; 1 cm = 10⁻² m &nbsp; | &nbsp; 1 mm = 10⁻³ m</div>
                <div class="formula">1 g = 10⁻³ kg &nbsp; | &nbsp; 1 h = 3600 s</div>
                <h3>উদাহরণ: 72 km h⁻¹-কে m s⁻¹-এ রূপান্তর</h3>
                <div class="formula">72 km/h = 72 × (1000 m / 3600 s) = 20 m s⁻¹</div>
            </section>

            <section id="dimension">
                <h2>১.৬ মাত্রা ও মাত্রা সমীকরণ (Dimensions)</h2>
                <p>কোনো ভৌত রাশি মৌলিক রাশিগুলোর কোন ঘাতে নির্ভর করে, তা প্রকাশকারী ঘাতগুলোকে ওই রাশির মাত্রা বলে। HSC পর্যায়ে ভর, দৈর্ঘ্য ও সময়ের মাত্রা যথাক্রমে M, L ও T দিয়ে প্রকাশ করা হয়।</p>
                <p>কোনো ভৌত রাশিকে মৌলিক রাশিগুলোর মাত্রা ও তাদের ঘাতের গুণফল হিসেবে প্রকাশ করাকে মাত্রা সমীকরণ বলে।</p>
                <h3>বেগ (Velocity)</h3>
                <div class="formula">v = সরণ/সময় &nbsp; ⇒ &nbsp; [v] = [LT⁻¹]</div>
                <h3>ত্বরণ (Acceleration)</h3>
                <div class="formula">a = বেগের পরিবর্তন/সময় &nbsp; ⇒ &nbsp; [a] = [LT⁻²]</div>
                <h3>বল (Force)</h3>
                <div class="formula">F = ma &nbsp; ⇒ &nbsp; [F] = [M][LT⁻²] = [MLT⁻²]</div>
                <h3>ভরবেগ (Momentum)</h3>
                <div class="formula">p = mv &nbsp; ⇒ &nbsp; [p] = [MLT⁻¹]</div>
                <h3>কাজ ও শক্তি (Work and Energy)</h3>
                <div class="formula">W = Fs &nbsp; ⇒ &nbsp; [W] = [ML²T⁻²]</div>
                <h3>ক্ষমতা (Power)</h3>
                <div class="formula">P = W/t &nbsp; ⇒ &nbsp; [P] = [ML²T⁻³]</div>
                <h3>চাপ (Pressure)</h3>
                <div class="formula">p = F/A &nbsp; ⇒ &nbsp; [p] = [ML⁻¹T⁻²]</div>
                <h3>ঘনত্ব (Density)</h3>
                <div class="formula">ρ = m/V &nbsp; ⇒ &nbsp; [ρ] = [ML⁻³]</div>
                <h3>মহাকর্ষীয় ধ্রুবক (Gravitational Constant)</h3>
                <div class="formula">F = Gm₁m₂/r² &nbsp; ⇒ &nbsp; [G] = [M⁻¹L³T⁻²]</div>
                <h3>গুরুত্বপূর্ণ মাত্রার তালিকা</h3>
                <table>
                    <thead>
                        <tr>
                            <th>রাশি</th>
                            <th>মাত্রা সমীকরণ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>ক্ষেত্রফল</td>
                            <td>[L²]</td>
                        </tr>
                        <tr>
                            <td>আয়তন</td>
                            <td>[L³]</td>
                        </tr>
                        <tr>
                            <td>বেগ</td>
                            <td>[LT⁻¹]</td>
                        </tr>
                        <tr>
                            <td>ত্বরণ</td>
                            <td>[LT⁻²]</td>
                        </tr>
                        <tr>
                            <td>বল</td>
                            <td>[MLT⁻²]</td>
                        </tr>
                        <tr>
                            <td>ভরবেগ</td>
                            <td>[MLT⁻¹]</td>
                        </tr>
                        <tr>
                            <td>কাজ/শক্তি</td>
                            <td>[ML²T⁻²]</td>
                        </tr>
                        <tr>
                            <td>ক্ষমতা</td>
                            <td>[ML²T⁻³]</td>
                        </tr>
                        <tr>
                            <td>চাপ</td>
                            <td>[ML⁻¹T⁻²]</td>
                        </tr>
                        <tr>
                            <td>ঘনত্ব</td>
                            <td>[ML⁻³]</td>
                        </tr>
                        <tr>
                            <td>স্প্রিং ধ্রুবক</td>
                            <td>[MT⁻²]</td>
                        </tr>
                        <tr>
                            <td>কম্পাঙ্ক</td>
                            <td>[T⁻¹]</td>
                        </tr>
                        <tr>
                            <td>মহাকর্ষীয় ধ্রুবক</td>
                            <td>[M⁻¹L³T⁻²]</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section id="dimensional-principle">
                <h2>১.৭ মাত্রার নীতি (Principle of Dimensional Homogeneity)</h2>
                <p>কোনো সঠিক ভৌত সমীকরণের উভয় পাশে এবং যোগ বা বিয়োগ চিহ্ন দ্বারা যুক্ত প্রতিটি পদের মাত্রা একই হতে হবে।</p>
                <h3>উদাহরণ: s = ut + ½at² যাচাই</h3>
                <div class="formula">বাম পাশে: [s] = [L]</div>
                <div class="formula">প্রথম পদ: [ut] = [LT⁻¹][T] = [L]</div>
                <div class="formula">দ্বিতীয় পদ: [at²] = [LT⁻²][T²] = [L]</div>
                <p>তিনটি পদের মাত্রাই দৈর্ঘ্য। তাই সমীকরণটি মাত্রাগতভাবে সঙ্গতিপূর্ণ।</p>
                <h3>মাত্রা বিশ্লেষণের ব্যবহার</h3>
                <ol>
                    <li>ভৌত সমীকরণের মাত্রাগত শুদ্ধতা যাচাই।</li>
                    <li>একটি রাশির সম্ভাব্য মাত্রা নির্ণয়।</li>
                    <li>বিভিন্ন একক পদ্ধতির মধ্যে রূপান্তরে সাহায্য।</li>
                    <li>ভৌত রাশিগুলোর সম্ভাব্য সম্পর্ক অনুমান।</li>
                </ol>
                <h3>সীমাবদ্ধতা</h3>
                <ul>
                    <li>সবসময় সংখ্যাগত ধ্রুবক নির্ণয় করা যায় না।</li>
                    <li>মাত্রাগতভাবে সঠিক হলেই সমীকরণ নিশ্চিতভাবে সঠিক নয়।</li>
                    <li>যোগ, বিয়োগ বা নির্দিষ্ট ফাংশনের প্রকৃতি মাত্রা বিশ্লেষণ একা সবসময় নির্ধারণ করতে পারে না।</li>
                </ul>
                <div class="callout"><strong>মনে রাখবে:</strong> একক ও মাত্রা এক বিষয় নয়। উদাহরণ: বলের SI একক N, কিন্তু মাত্রা [MLT⁻²]।</div>
            </section>

            <section id="dimensional-analysis">
                <h2>১.৮ মাত্রার সাহায্যে সূত্র গঠন</h2>
                <p>ধরা যাক, সরল দোলকের পর্যায়কাল T, দৈর্ঘ্য l এবং অভিকর্ষজ ত্বরণ g-এর ওপর নির্ভরশীল।</p>
                <div class="formula">T = k lᵃ gᵇ</div>
                <p>এখানে k একটি মাত্রাহীন ধ্রুবক এবং a, b অজানা ঘাত। মাত্রা বসিয়ে পাই—</p>
                <div class="formula">[T] = [L]ᵃ[LT⁻²]ᵇ = [Lᵃ⁺ᵇ T⁻²ᵇ]</div>
                <p>মাত্রার ঘাত তুলনা করে:</p>
                <div class="formula">a + b = 0 &nbsp; এবং &nbsp; −2b = 1</div>
                <div class="formula">b = −½, &nbsp; a = ½</div>
                <div class="formula">T = k√(l/g)</div>
                <p>মাত্রা বিশ্লেষণ সম্পর্কটির রূপ দেয়, কিন্তু k-এর মান দেয় না। ক্ষুদ্র দোলনের জন্য প্রকৃত সূত্রে k = 2π।</p>
            </section>

            <section id="errors">
                <h2>১.৯ পরিমাপের ত্রুটি (Errors in Measurement)</h2>
                <p>কোনো ভৌত রাশির পরিমাপকৃত মান ও প্রকৃত বা গৃহীত রেফারেন্স মানের মধ্যে পার্থক্য থাকলে তাকে পরিমাপের ত্রুটি বলে। বাস্তবে প্রকৃত মান সবসময় জানা যায় না; তাই পরিমাপের অনিশ্চয়তা নির্ণয়ে রেফারেন্স মান, যন্ত্রের সীমাবদ্ধতা ও পদ্ধতি বিবেচনা করা হয়।</p>
                <div class="grid-cards">
                    <div class="mini-card">
                        <h3>যান্ত্রিক ত্রুটি</h3>
                        <p>Instrumental Error: শূন্য ত্রুটি, যন্ত্রের ক্ষয় বা ভুল ক্যালিব্রেশনের কারণে।</p>
                    </div>
                    <div class="mini-card">
                        <h3>পর্যবেক্ষণজনিত ত্রুটি</h3>
                        <p>Observational Error: পাঠ নেওয়ার সময় চোখের অবস্থান বা পাঠ বোঝার ভুলের কারণে।</p>
                    </div>
                    <div class="mini-card">
                        <h3>এলোমেলো ত্রুটি</h3>
                        <p>Random Error: পরিবেশ বা পরিমাপের ক্ষুদ্র পরিবর্তনে অনিয়মিত বিচ্যুতি।</p>
                    </div>
                    <div class="mini-card">
                        <h3>প্রণালীগত ত্রুটি</h3>
                        <p>Systematic Error: কোনো নির্দিষ্ট কারণে ফল বারবার একই দিকে বিচ্যুত হওয়া।</p>
                    </div>
                </div>
                <div class="callout"><strong>গুরুত্বপূর্ণ:</strong> Error মানেই অসাবধানতাজনিত mistake নয়। সঠিক পদ্ধতিতে মাপ নিলেও যন্ত্রের সীমাবদ্ধতা ও অন্যান্য কারণে অনিশ্চয়তা থাকতে পারে।</div>
            </section>

            <section id="error-math">
                <h2>১.১০ পরম, আপেক্ষিক ও শতকরা ত্রুটি</h2>
                <h3>ক. পরম ত্রুটি (Absolute Error)</h3>
                <div class="formula">পরম ত্রুটি = |x − x₀|</div>
                <p>একই রাশি n বার মাপলে গড় মান:</p>
                <div class="formula">x̄ = (x₁ + x₂ + … + xₙ)/n</div>
                <p>গড় পরম বিচ্যুতি:</p>
                <div class="formula">Δx̄ = Σ|xᵢ − x̄|/n</div>
                <p>ফল লেখা যায়: <strong>x = x̄ ± Δx̄</strong>।</p>
                <h3>খ. আপেক্ষিক ত্রুটি (Relative Error)</h3>
                <div class="formula">আপেক্ষিক ত্রুটি = Δx/|x|</div>
                <h3>গ. শতকরা ত্রুটি (Percentage Error)</h3>
                <div class="formula">শতকরা ত্রুটি = (Δx/|x|) × 100%</div>
                <h3>উদাহরণ ১: শতকরা ত্রুটি</h3>
                <p>একটি দণ্ডের দৈর্ঘ্য (2.00 ± 0.02) m। শতকরা ত্রুটি নির্ণয় করো।</p>
                <div class="formula">শতকরা ত্রুটি = (0.02/2.00) × 100% = 1%</div>
                <h3>যোগ ও বিয়োগ</h3>
                <p>যদি z = x + y অথবা z = x − y হয়, সর্বোচ্চ পরম অনিশ্চয়তা:</p>
                <div class="formula">Δz = Δx + Δy</div>
                <h3>গুণ ও ভাগ</h3>
                <p>যদি z = xy অথবা z = x/y হয়, ক্ষুদ্র অনিশ্চয়তার জন্য:</p>
                <div class="formula">Δz/|z| ≈ Δx/|x| + Δy/|y|</div>
                <h3>ঘাত</h3>
                <p>যদি z = xⁿ হয়:</p>
                <div class="formula">Δz/|z| ≈ |n| Δx/|x|</div>
                <h3>উদাহরণ ২: বৃত্তের ক্ষেত্রফলের ত্রুটি</h3>
                <p>ব্যাসার্ধ (5.0 ± 0.1) cm হলে ক্ষেত্রফলের শতকরা ত্রুটি:</p>
                <div class="formula">A = πr² ⇒ ΔA/A ≈ 2Δr/r</div>
                <div class="formula">শতকরা ত্রুটি ≈ 2 × (0.1/5.0) × 100% = 4%</div>
            </section>

            <section id="accuracy">
                <h2>১.১১ সঠিকতা ও সূক্ষ্মতা (Accuracy and Precision)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>বিষয়</th>
                            <th>সঠিকতা (Accuracy)</th>
                            <th>সূক্ষ্মতা (Precision)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>অর্থ</td>
                            <td>রেফারেন্স মানের কাছাকাছি হওয়া</td>
                            <td>বারবার মাপলে ফলের পারস্পরিক সামঞ্জস্য</td>
                        </tr>
                        <tr>
                            <td>নির্ভর করে</td>
                            <td>বিচ্যুতি ও ক্যালিব্রেশনের ওপর</td>
                            <td>পুনরাবৃত্ত ফলের বিচ্ছুরণের ওপর</td>
                        </tr>
                        <tr>
                            <td>উদাহরণ</td>
                            <td>প্রকৃত মান 10.0 হলে 10.1 কাছাকাছি</td>
                            <td>9.5, 9.5, 9.6 প্রায় একই রকম</td>
                        </tr>
                    </tbody>
                </table>
                <ul>
                    <li><strong>উচ্চ Accuracy ও উচ্চ Precision:</strong> ফলগুলো রেফারেন্স মানের কাছাকাছি এবং নিজেদের মধ্যেও কাছাকাছি।</li>
                    <li><strong>উচ্চ Accuracy, কম Precision:</strong> ফলের গড় রেফারেন্স মানের কাছাকাছি, কিন্তু ফলগুলো বেশি ছড়ানো।</li>
                    <li><strong>কম Accuracy, উচ্চ Precision:</strong> ফলগুলো পরস্পরের কাছাকাছি, কিন্তু রেফারেন্স মান থেকে দূরে।</li>
                    <li><strong>কম Accuracy ও কম Precision:</strong> ফলগুলো পরস্পর থেকেও দূরে এবং রেফারেন্স মান থেকেও দূরে।</li>
                </ul>
            </section>

            <section id="significant">
                <h2>১.১২ তাৎপর্যপূর্ণ অঙ্ক (Significant Figures)</h2>
                <p>কোনো পরিমাপকৃত সংখ্যায় নিশ্চিত অঙ্ক এবং সর্বশেষ আনুমানিক অঙ্ক মিলিয়ে যে অঙ্কগুলো পরিমাপের তথ্য বহন করে, সেগুলোকে তাৎপর্যপূর্ণ অঙ্ক বলে।</p>
                <h3>গণনার নিয়ম</h3>
                <ol>
                    <li>শূন্য ছাড়া সব অঙ্ক তাৎপর্যপূর্ণ। যেমন 347-এ ৩টি।</li>
                    <li>দুটি তাৎপর্যপূর্ণ অঙ্কের মাঝখানের শূন্য তাৎপর্যপূর্ণ। যেমন 1002-এ ৪টি।</li>
                    <li>প্রথম অশূন্য অঙ্কের আগের শূন্য তাৎপর্যপূর্ণ নয়। যেমন 0.0045-এ ২টি।</li>
                    <li>দশমিক সংখ্যার শেষে থাকা শূন্য তাৎপর্যপূর্ণ। যেমন 2.500-এ ৪টি।</li>
                    <li>পূর্ণসংখ্যার শেষের শূন্য অস্পষ্ট হতে পারে। বৈজ্ঞানিক সংকেত ব্যবহার করো।</li>
                </ol>
                <div class="formula">1.5 × 10³ → ২টি তাৎপর্যপূর্ণ অঙ্ক</div>
                <div class="formula">1.500 × 10³ → ৪টি তাৎপর্যপূর্ণ অঙ্ক</div>
                <h3>গুণ ও ভাগ</h3>
                <p>ফলাফল সাধারণত ইনপুটগুলোর মধ্যে সবচেয়ে কম তাৎপর্যপূর্ণ অঙ্কবিশিষ্ট সংখ্যার সমান সংখ্যক তাৎপর্যপূর্ণ অঙ্কে প্রকাশ করা হয়।</p>
                <div class="formula">2.5 × 3.42 = 8.55 ≈ 8.6</div>
                <h3>যোগ ও বিয়োগ</h3>
                <p>ফলাফলে দশমিক স্থানের সংখ্যা নির্ধারিত হয় সবচেয়ে কম দশমিক স্থানবিশিষ্ট সংখ্যার ভিত্তিতে।</p>
                <div class="formula">12.11 + 0.3 = 12.41 ≈ 12.4</div>
                <h3>বৈজ্ঞানিক সংকেত (Scientific Notation)</h3>
                <div class="formula">N = a × 10ⁿ, যেখানে 1 ≤ |a| &lt; 10</div>
                <div class="formula">3000000 = 3 × 10⁶ &nbsp; | &nbsp; 0.00045 = 4.5 × 10⁻⁴</div>
            </section>

            <section id="instruments">
                <h2>১.১৩ পরিমাপক যন্ত্র ও লঘিষ্ঠ গণন (Least Count)</h2>
                <p>কোনো যন্ত্র দিয়ে নির্ভরযোগ্যভাবে পরিমাপযোগ্য ক্ষুদ্রতম পাঠের ব্যবধানকে সাধারণভাবে যন্ত্রের লঘিষ্ঠ গণন (Least Count) বলা হয়।</p>
                <h3>ক. ভার্নিয়ার ক্যালিপার্স (Vernier Calipers)</h3>
                <p>বস্তুর বাইরের ব্যাস, ভেতরের ব্যাস এবং গভীরতা মাপতে ব্যবহার করা হয়। এর প্রধান অংশ: Main Scale, Vernier Scale, External Jaws, Internal Jaws এবং Depth Rod।</p>
                <div class="formula">n VSD = m MSD</div>
                <div class="formula">LC = 1 MSD − 1 VSD</div>
                <div class="formula">পাঠ = প্রধান স্কেলের পাঠ + (মিলে যাওয়া ভার্নিয়ার দাগ × LC)</div>
                <h3>ভার্নিয়ার ক্যালিপার্সের উদাহরণ</h3>
                <p>প্রধান স্কেলের ১ ভাগ = 1 mm এবং ভার্নিয়ারের ১০ ভাগ = প্রধান স্কেলের ৯ ভাগ। ভার্নিয়ারের ৬ নম্বর দাগ মিলে গেছে এবং প্রধান স্কেলের পাঠ 2.3 cm। শূন্য ত্রুটি নেই।</p>
                <p><strong>ধাপ ১:</strong> 10 VSD = 9 mm ⇒ 1 VSD = 0.9 mm</p>
                <p><strong>ধাপ ২:</strong> LC = 1.0 − 0.9 = 0.1 mm</p>
                <p><strong>ধাপ ৩:</strong> ভার্নিয়ার পাঠ = 6 × 0.1 = 0.6 mm</p>
                <p><strong>ধাপ ৪:</strong> মোট পাঠ = 23 mm + 0.6 mm = 23.6 mm</p>
                <div class="formula">উত্তর = 2.36 cm</div>
                <h3>খ. স্ক্রু গজ (Screw Gauge)</h3>
                <p>তার, পাতলা পাত বা ক্ষুদ্র বস্তুর ব্যাস ও পুরুত্ব পরিমাপ করতে ব্যবহার করা হয়।</p>
                <div class="formula">Pitch = অতিক্রান্ত দূরত্ব / পূর্ণ ঘূর্ণনের সংখ্যা</div>
                <div class="formula">LC = Pitch / বৃত্তাকার স্কেলের মোট ভাগ সংখ্যা</div>
                <div class="formula">পাঠ = প্রধান স্কেলের পাঠ + (বৃত্তাকার স্কেলের পাঠ × LC)</div>
                <h3>স্ক্রু গজের উদাহরণ</h3>
                <p>পিচ 0.5 mm, বৃত্তাকার স্কেলে ৫০টি ভাগ, প্রধান স্কেলের পাঠ 5.5 mm, বৃত্তাকার স্কেলের পাঠ ২৪ এবং শূন্য ত্রুটি নেই।</p>
                <p><strong>ধাপ ১:</strong> LC = 0.5/50 = 0.01 mm</p>
                <p><strong>ধাপ ২:</strong> বৃত্তাকার স্কেলের পাঠ = 24 × 0.01 = 0.24 mm</p>
                <p><strong>ধাপ ৩:</strong> মোট পাঠ = 5.5 + 0.24 = 5.74 mm</p>
                <div class="formula">উত্তর = 5.74 mm</div>
                <h3>শূন্য ত্রুটি ও শূন্য সংশোধন</h3>
                <ul>
                    <li><strong>ধনাত্মক শূন্য ত্রুটি:</strong> শূন্য অবস্থায় যন্ত্র শূন্যের চেয়ে বেশি পাঠ দেখায়।</li>
                    <li><strong>ঋণাত্মক শূন্য ত্রুটি:</strong> শূন্য অবস্থায় যন্ত্র শূন্যের চেয়ে কম পাঠ দেখায়।</li>
                </ul>
                <div class="formula">সংশোধিত পাঠ = পর্যবেক্ষিত পাঠ − শূন্য ত্রুটি</div>
                <p>শূন্য সংশোধন হলো শূন্য ত্রুটির বিপরীত চিহ্নযুক্ত মান।</p>
            </section>

            <section id="improve">
                <h2>১.১৪ পরিমাপের মান উন্নত করার উপায়</h2>
                <ol>
                    <li>উপযুক্ত ও ক্যালিব্রেট করা পরিমাপক যন্ত্র ব্যবহার করা।</li>
                    <li>পরিমাপের আগে যন্ত্রের শূন্য ত্রুটি পরীক্ষা করা।</li>
                    <li>স্কেলের পাঠ চোখ সোজা রেখে নেওয়া, যাতে Parallax Error কমে।</li>
                    <li>একই রাশি একাধিকবার পরিমাপ করে গড় মান নির্ণয় করা।</li>
                    <li>যন্ত্রের লঘিষ্ঠ গণন বিবেচনা করে ফল প্রকাশ করা।</li>
                    <li>পরিমাপের সঙ্গে উপযুক্ত একক ও প্রয়োজনীয় তাৎপর্যপূর্ণ অঙ্ক ব্যবহার করা।</li>
                </ol>
            </section>

            <section id="formula-sheet">
                <h2>সূত্র সংকলন (Formula Sheet)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>বিষয়</th>
                            <th>সূত্র</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>ভৌত রাশি</td>
                            <td>Q = nu</td>
                        </tr>
                        <tr>
                            <td>গড় পরিমাপ</td>
                            <td>x̄ = Σxᵢ/n</td>
                        </tr>
                        <tr>
                            <td>আপেক্ষিক ত্রুটি</td>
                            <td>Δx/|x|</td>
                        </tr>
                        <tr>
                            <td>শতকরা ত্রুটি</td>
                            <td>(Δx/|x|) × 100%</td>
                        </tr>
                        <tr>
                            <td>গুণফলের আপেক্ষিক ত্রুটি</td>
                            <td>Δz/|z| ≈ Δx/|x| + Δy/|y|</td>
                        </tr>
                        <tr>
                            <td>ঘাতের আপেক্ষিক ত্রুটি</td>
                            <td>Δz/|z| ≈ |n|Δx/|x|, যখন z = xⁿ</td>
                        </tr>
                        <tr>
                            <td>বেগের মাত্রা</td>
                            <td>[LT⁻¹]</td>
                        </tr>
                        <tr>
                            <td>ত্বরণের মাত্রা</td>
                            <td>[LT⁻²]</td>
                        </tr>
                        <tr>
                            <td>বলের মাত্রা</td>
                            <td>[MLT⁻²]</td>
                        </tr>
                        <tr>
                            <td>কাজের মাত্রা</td>
                            <td>[ML²T⁻²]</td>
                        </tr>
                        <tr>
                            <td>ক্ষমতার মাত্রা</td>
                            <td>[ML²T⁻³]</td>
                        </tr>
                        <tr>
                            <td>ভার্নিয়ার LC</td>
                            <td>1 MSD − 1 VSD</td>
                        </tr>
                        <tr>
                            <td>স্ক্রু গজ LC</td>
                            <td>Pitch / বৃত্তাকার স্কেলের ভাগ সংখ্যা</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section id="practice">
                <h2>অধ্যায়ভিত্তিক অনুশীলনী</h2>
                <h3>ক. জ্ঞানমূলক ও সংক্ষিপ্ত প্রশ্ন</h3>
                <ol>
                    <li>পদার্থবিজ্ঞান কাকে বলে?</li>
                    <li>ভৌত রাশি ও একক কী?</li>
                    <li>মৌলিক রাশি এবং লব্ধ রাশির মধ্যে পার্থক্য লেখো।</li>
                    <li>SI পদ্ধতির সাতটি মৌলিক রাশি ও এককের নাম লেখো।</li>
                    <li>মাত্রা ও মাত্রা সমীকরণ বলতে কী বোঝায়?</li>
                    <li>মাত্রার নীতি বিবৃত করো।</li>
                    <li>পরম ত্রুটি, আপেক্ষিক ত্রুটি ও শতকরা ত্রুটি কী?</li>
                    <li>Accuracy ও Precision-এর পার্থক্য লেখো।</li>
                    <li>তাৎপর্যপূর্ণ অঙ্ক কী?</li>
                    <li>ভার্নিয়ার ক্যালিপার্স ও স্ক্রু গজের লঘিষ্ঠ গণন কীভাবে নির্ণয় করা হয়?</li>
                </ol>
                <h3>খ. গাণিতিক সমস্যা</h3>
                <div class="mini-card">
                    <p><strong>প্রশ্ন ১.</strong> 90 km h⁻¹-কে m s⁻¹-এ রূপান্তর করো।</p>
                    <details>
                        <summary>সমাধান দেখো</summary>
                        <div class="formula">90 × (1000/3600) = 25 m s⁻¹</div>
                    </details>
                </div>
                <div class="mini-card">
                    <p><strong>প্রশ্ন ২.</strong> একটি বস্তুর ভর (10.0 ± 0.1) kg। ভরের শতকরা ত্রুটি নির্ণয় করো।</p>
                    <details>
                        <summary>সমাধান দেখো</summary>
                        <div class="formula">(0.1/10.0) × 100% = 1%</div>
                    </details>
                </div>
                <div class="mini-card">
                    <p><strong>প্রশ্ন ৩.</strong> v = at সমীকরণটির মাত্রাগত সামঞ্জস্য যাচাই করো।</p>
                    <details>
                        <summary>সমাধান দেখো</summary>
                        <div class="formula">[at] = [LT⁻²][T] = [LT⁻¹] = [v]</div>
                        <p>অতএব সমীকরণটি মাত্রাগতভাবে সঙ্গতিপূর্ণ।</p>
                    </details>
                </div>
                <div class="mini-card">
                    <p><strong>প্রশ্ন ৪.</strong> একটি বৃত্তের ব্যাসার্ধের শতকরা ত্রুটি 2%। ক্ষেত্রফলের শতকরা ত্রুটি কত?</p>
                    <details>
                        <summary>সমাধান দেখো</summary>
                        <div class="formula">A = πr² ⇒ শতকরা ত্রুটি ≈ 2 × 2% = 4%</div>
                    </details>
                </div>
                <div class="mini-card">
                    <p><strong>প্রশ্ন ৫.</strong> 0.003040 সংখ্যাটিতে কয়টি তাৎপর্যপূর্ণ অঙ্ক আছে?</p>
                    <details>
                        <summary>উত্তর দেখো</summary>
                        <p>৪টি: 3, 0, 4, 0। প্রথমের শূন্যগুলো তাৎপর্যপূর্ণ নয়।</p>
                    </details>
                </div>

                <h3>গ. MCQ অনুশীলন</h3>
                <div class="question" data-answer="2" data-explanation="কেলভিন তাপগতীয় তাপমাত্রার SI মৌলিক একক।">
                    <strong>১. নিচের কোনটি SI-এর মৌলিক একক?</strong><br>
                    <button type="button" data-choice="0">A. নিউটন</button><button type="button" data-choice="1">B. জুল</button><button type="button" data-choice="2">C. কেলভিন</button><button type="button" data-choice="3">D. ওয়াট</button>
                    <div class="feedback" aria-live="polite"></div>
                </div>
                <div class="question" data-answer="1" data-explanation="F = ma; তাই [F] = [M][LT⁻²] = [MLT⁻²]।">
                    <strong>২. বলের মাত্রা কোনটি?</strong><br>
                    <button type="button" data-choice="0">A. [MLT⁻¹]</button><button type="button" data-choice="1">B. [MLT⁻²]</button><button type="button" data-choice="2">C. [ML²T⁻²]</button><button type="button" data-choice="3">D. [ML⁻¹T⁻²]</button>
                    <div class="feedback" aria-live="polite"></div>
                </div>
                <div class="question" data-answer="1" data-explanation="প্রথমের শূন্যগুলো তাৎপর্যপূর্ণ নয়; 5, 2 এবং শেষের 0 তাৎপর্যপূর্ণ।">
                    <strong>৩. 0.00520 সংখ্যাটিতে কয়টি তাৎপর্যপূর্ণ অঙ্ক আছে?</strong><br>
                    <button type="button" data-choice="0">A. ২টি</button><button type="button" data-choice="1">B. ৩টি</button><button type="button" data-choice="2">C. ৪টি</button><button type="button" data-choice="3">D. ৫টি</button>
                    <div class="feedback" aria-live="polite"></div>
                </div>
                <div class="question" data-answer="1" data-explanation="Least Count = Pitch / বৃত্তাকার স্কেলের মোট ভাগ সংখ্যা।">
                    <strong>৪. স্ক্রু গজের লঘিষ্ঠ গণনের সূত্র কোনটি?</strong><br>
                    <button type="button" data-choice="0">A. Pitch × ভাগ সংখ্যা</button><button type="button" data-choice="1">B. Pitch ÷ ভাগ সংখ্যা</button><button type="button" data-choice="2">C. ভাগ সংখ্যা ÷ Pitch</button><button type="button" data-choice="3">D. Pitch + ভাগ সংখ্যা</button>
                    <div class="feedback" aria-live="polite"></div>
                </div>
                <div class="question" data-answer="2" data-explanation="ঘাতের ক্ষেত্রে শতকরা ত্রুটি প্রায় ঘাতের মানের গুণিতক: 2 × 3% = 6%।">
                    <strong>৫. x²-এর সমানুপাতিক রাশিতে x-এর শতকরা ত্রুটি 3% হলে রাশিটির আনুমানিক শতকরা ত্রুটি কত?</strong><br>
                    <button type="button" data-choice="0">A. 1.5%</button><button type="button" data-choice="1">B. 3%</button><button type="button" data-choice="2">C. 6%</button><button type="button" data-choice="3">D. 9%</button>
                    <div class="feedback" aria-live="polite"></div>
                </div>
                <div class="actions">
                    <button type="button" onclick="window.print()">প্রিন্ট / PDF হিসেবে সংরক্ষণ</button>
                    <button type="button" class="secondary" onclick="location.reload()">MCQ আবার শুরু</button>
                </div>
            </section>

            <section>
                <h2>পরীক্ষার আগে দ্রুত রিভিশন</h2>
                <ul>
                    <li>সাতটি মৌলিক রাশি ও একক মুখস্থ রাখতে হবে।</li>
                    <li>বেগ, ত্বরণ, বল, কাজ, ক্ষমতা ও চাপের মাত্রা অনুশীলন করতে হবে।</li>
                    <li>মাত্রার নীতি ব্যবহার করে সূত্র যাচাই করার ধাপ জানতে হবে।</li>
                    <li>আপেক্ষিক ও শতকরা ত্রুটি নির্ণয়ের সূত্র মনে রাখতে হবে।</li>
                    <li>গুণ, ভাগ ও ঘাতের ক্ষেত্রে ত্রুটি নির্ণয়ের নিয়ম বুঝতে হবে।</li>
                    <li>Significant Figures-এর নিয়ম এবং যোগ-বিয়োগ বনাম গুণ-ভাগের পার্থক্য আয়ত্ত করতে হবে।</li>
                    <li>Vernier Calipers ও Screw Gauge-এর লঘিষ্ঠ গণন, পাঠ এবং শূন্য সংশোধন অনুশীলন করতে হবে।</li>
                </ul>
                <div class="warning"><strong>শিক্ষক-ব্যবহারের পরামর্শ:</strong> ক্লাসে প্রথমে ভৌত রাশি ও একক, তারপর মাত্রা বিশ্লেষণ, এরপর ত্রুটি ও তাৎপর্যপূর্ণ অঙ্ক এবং সবশেষে ব্যবহারিক পরিমাপক যন্ত্র পড়ালে শিক্ষার্থীদের ধারণা ধাপে ধাপে পরিষ্কার হবে।</div>
            </section>
        </main>
    </div>
    <footer>
        <p>© <?= $year ?> · HSC Physics 1st Paper — Chapter 1: Physical World and Measurement</p>
        <p>শিক্ষামূলক ক্লাস নোট। ব্যবহারের আগে নিজ প্রতিষ্ঠানের সর্বশেষ NCTB পাঠ্যবই ও সিলেবাসের সঙ্গে মিলিয়ে নিন।</p>
    </footer>
    <script>
        // Typeset equation-like formula boxes with MathJax. Bengali explanatory formulas
        // remain readable as text; Latin/symbolic equations use mathematical notation.
        (function() {
            const superscripts = {
                '⁰': '0',
                '¹': '1',
                '²': '2',
                '³': '3',
                '⁴': '4',
                '⁵': '5',
                '⁻': '-'
            };
            const subscripts = {
                '₀': '0',
                '₁': '1',
                '₂': '2',
                '₃': '3',
                '₄': '4',
                '₅': '5',
                'ᵢ': 'i',
                'ₙ': 'n'
            };

            function toTex(raw) {
                let t = raw.replace(/×/g, ' \\times ').replace(/⇒/g, ' \\Rightarrow ')
                    .replace(/≈/g, ' \\approx ').replace(/Δ/g, ' \\Delta ')
                    .replace(/Σ/g, ' \\sum ').replace(/π/g, ' \\pi ').replace(/ρ/g, ' \\rho ')
                    .replace(/√\(([^()]*)\)/g, '\\sqrt{$1}');
                t = t.replace(/([A-Za-z])([⁰¹²³⁴⁵⁻]+)/g, function(_, base, exp) {
                    return base + '^{' + Array.from(exp).map(c => superscripts[c] ?? c).join('') + '}';
                });
                t = t.replace(/([A-Za-z])([₀₁₂₃₄₅ᵢₙ]+)/g, function(_, base, sub) {
                    return base + '_{' + Array.from(sub).map(c => subscripts[c] ?? c).join('') + '}';
                });
                return t;
            }
            document.querySelectorAll('.formula').forEach(function(el) {
                const raw = el.textContent.replace(/\s+/g, ' ').trim();
                // MathJax's default fonts do not reliably contain Bengali glyphs, so only
                // convert boxes made entirely from Latin letters, numerals and math symbols.
                if (raw && !/[\u0980-\u09FF]/.test(raw)) {
                    el.textContent = '\\[' + toTex(raw) + '\\]';
                }
            });
            window.addEventListener('load', function() {
                if (window.MathJax && MathJax.typesetPromise) MathJax.typesetPromise();
            });
        })();

        document.querySelectorAll('.question button[data-choice]').forEach(function(button) {
            button.addEventListener('click', function() {
                const question = button.closest('.question');
                const chosen = button.dataset.choice;
                const correct = question.dataset.answer;
                const feedback = question.querySelector('.feedback');
                question.querySelectorAll('button[data-choice]').forEach(function(item) {
                    item.style.borderColor = '#c6d2e2';
                    item.style.background = '#fff';
                });
                button.style.borderColor = chosen === correct ? '#087443' : '#b42318';
                button.style.background = chosen === correct ? '#e8f7ee' : '#fff0ef';
                feedback.className = 'feedback ' + (chosen === correct ? 'correct' : 'incorrect');
                feedback.textContent = (chosen === correct ? 'সঠিক উত্তর! ' : 'সঠিক উত্তরটি: ') + question.querySelector('button[data-choice="' + correct + '"]').textContent + ' — ' + question.dataset.explanation;
            });
        });
    </script>
</body>

</html>