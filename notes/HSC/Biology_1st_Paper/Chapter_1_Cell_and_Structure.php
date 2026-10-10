<?php
$pageTitle = "HSC Biology 1st Paper — Chapter 1: কোষ ও এর গঠন";
$currentYear = date("Y");
?>
<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="HSC Biology 1st Paper Chapter 1 Cell and its Structure — Comprehensive NCTB-aligned study notes with cell organelles, plasma membrane, nucleus, chromosome, and MCQ practice.">
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

        .pill {
            display: inline-block;
            background: #e4effa;
            color: #214f7c;
            border-radius: 99px;
            padding: 2px 9px;
            font-size: .8rem;
            margin-bottom: 6px;
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
                default: none !important;
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
            <span class="pill">HSC Biology 1st Paper • NCTB-aligned study notes</span>
            <h1>অধ্যায় ১: কোষ ও এর গঠন (Cell and its Structure)</h1>
            <p>কোষের ধারণা, আবিষ্কারের ইতিহাস, কোষ প্রাচীর, ফ্লুইড মোজাইক মডেল, অঙ্গাণুসমূহ, নিউক্লিয়াস ও ক্রোমোজোম</p>
            <div class="toolbar"><button onclick="window.print()">প্রিন্ট / Save as PDF</button><button onclick="document.querySelectorAll('.quiz').forEach(q=>{q.querySelectorAll('input').forEach(i=>i.checked=false);q.querySelector('.feedback').textContent=''})">MCQ রিসেট</button></div>
        </div>
    </header>

    <div class="layout">
        <nav aria-label="Chapter navigation"><strong>এই অধ্যায়ে</strong>
            <div class="links">
                <a href="#intro">১. কোষের ধারণা ও ইতিহাস</a>
                <a href="#types">২. কোষের প্রকারভেদ</a>
                <a href="#envelopes">৩. কোষের বহিরাবরণ</a>
                <a href="#organelles">৪. সাইটোপ্লাজমীয় অঙ্গাণু</a>
                <a href="#nucleus">৫. নিউক্লিয়াস ও ক্রোমোজোম</a>
                <a href="#practice">৬. অনুশীলনী</a>
                <a href="#quiz">৭. MCQ পরীক্ষা</a>
            </div>
        </nav>

        <main class="content">

            <section class="section" id="intro">
                <h2>১. কোষের ধারণা ও আবিষ্কারের ইতিহাস</h2>
                <p class="lead">জীবদেহের গঠন ও কাজের একককে <b>কোষ (Cell)</b> বলা হয়। অর্থাৎ, সকল জীব এক বা একাধিক কোষ দ্বারা গঠিত এবং কোষ হলো জীবনের মৌলিক ও জৈবিক একক।</p>
                <ul>
                    <li><b>রবার্ট হুক (১৬৬৫):</b> ছিপিপি বা কর্কের পাতলা অংশ অণুবীক্ষণ যন্ত্রে পর্যবেক্ষণ করে মৌচাকের মতো প্রকোষ্ঠ দেখতে পান এবং এর নাম দেন 'Cell' বা কোষ।</li>
                    <li><b>অ্যান্টন ভন লিউভেনহুক (১৬৭৪):</b> প্রথম জীবিত কোষ (যেমন- ব্যাকটেরিয়া, প্রোটোজোয়া, লোহিত রক্তকণিকা ও শুক্রাণু) আবিষ্কার করেন।</li>
                    <li><b>কোষ তত্ত্ব (Cell Theory):</b> ১৮৩৮-৩৯ সালে উদ্ভিদবিজ্ঞানী ম্যাথিয়াস স্লেইডেন এবং প্রাণবিজ্ঞানী থিওডোর শোয়ান কোষ তত্ত্ব প্রদান করেন। পরবর্তীতে রুডলফ ভিরচাও (১৮৫৫) এর সাথে যোগ করেন যে নতুন কোষের সৃষ্টি পূর্ববর্তী কোষ থেকেই হয়।</li>
                </ul>
            </section>

            <section class="section" id="types">
                <h2>২. কোষের প্রকারভেদ ও তুলনামূলক আলোচনা</h2>
                <p>গঠন ও নিউক্লিয়াসের কাঠামোর ভিত্তিতে কোষ প্রধানত দুই প্রকার:</p>
                <table>
                    <thead>
                        <tr>
                            <th>বৈশিষ্ট্য</th>
                            <th>প্রাককেন্দ্রিক কোষ (Prokaryotic)</th>
                            <th>ইউক্যারিওটিক কোষ (Eukaryotic)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>কেন্দ্রিকা (Nucleus)</td>
                            <td>সুগঠিত নয় (নিউক্লিওয়েড বা নিউক্লীয় বস্তু থাকে)</td>
                            <td>সুগঠিত (ঝিল্লিযুক্ত নিউক্লিয়াস থাকে)</td>
                        </tr>
                        <tr>
                            <td>রাইবোজোম</td>
                            <td>70S ধরনের</td>
                            <td>80S (মাইটোকন্ড্রিয়া/ক্লোরোপ্লাস্টে 70S)</td>
                        </tr>
                        <tr>
                            <td>ঝিল্লিযুক্ত অঙ্গাণু</td>
                            <td>মাইটোকন্ড্রিয়া, গলগি বডি, ই.আর. অনুপস্থিত</td>
                            <td>উপস্থিত</td>
                        </tr>
                        <tr>
                            <td>ডিএনএ (DNA)</td>
                            <td>বৃত্তাকার, হিস্টোন প্রোটিনহীন</td>
                            <td>সূত্রাকার, হিস্টোন প্রোটিনযুক্ত</td>
                        </tr>
                        <tr>
                            <td>উদাহরণ</td>
                            <td>ব্যাকটেরিয়া, সায়ানোব্যাকটেরিয়া</td>
                            <td>উদ্ভিদ ও প্রাণী কোষ</td>
                        </tr>
                    </tbody>
                </table>
                <h3>উদ্ভিদ কোষ বনাম প্রাণী কোষ</h3>
                <ul>
                    <li><b>কোষ প্রাচীর:</b> উদ্ভিদ কোষে সেলুলোজ নির্মিত শক্ত প্রাচীর থাকে; প্রাণী কোষে নেই।</li>
                    <li><b>প্লাস্টিড:</b> উদ্ভিদ কোষে প্লাস্টিড উপস্থিত (সালোকসংশ্লেষণে সক্ষম); প্রাণী কোষে অনুপস্থিত।</li>
                    <li><b>সেন্ট্রোসোম:</b> প্রাণী কোষে সেন্ট্রিওলসহ সেন্ট্রোজোম থাকে; উচ্চ শ্রেণির উদ্ভিদ কোষে সাধারণত অনুপস্থিত।</li>
                </ul>
            </section>

            <section class="section" id="envelopes">
                <h2>৩. কোষের বহিরাবরণ (Cell Envelopes)</h2>
                <h3>ক. কোষ প্রাচীর (Cell Wall)</h3>
                <p>উদ্ভিদ কোষের প্লাজমা ঝিল্লির বাইরে জেলি সদৃশ মৃত বা জড় বস্তু দিয়ে তৈরি যে দৃঢ় আবরণ থাকে তাকে কোষ প্রাচীর বলে। এটি সেলুলোজ, হেমিসেলুলোজ, পেকটিন ও লিগিন দ্বারা গঠিত। এর প্রধান স্তরগুলো হলো মিডল ল্যামেলা, প্রাইমারি প্রাচীর ও সেকেন্ডারি প্রাচীর।</p>
                <h3>খ. কোষ ঝিল্লি বা প্লাজমা মেমব্রেন (Plasma Membrane)</h3>
                <p>প্রোটোপ্লাজমকে ঘিরে যে সজীব, স্থিতিস্থাপক ও বৈষম্যভেদ্য ঝিল্লি থাকে তাকে প্লাজমা মেমব্রেন বলে।</p>
                <div class="callout"><b>ফ্লুইড মোজাইক মডেল (Fluid Mosaic Model):</b> ১৯৭২ সালে বিজ্ঞানী এস. জে. সিঙ্গার এবং জি. নিকলসন কোষ ঝিল্লির সুপরিচিত এই মডেল প্রদান করেন। তাদের মতে, এটি ফসফোলিপিড বাইলেয়ার (দ্বি-স্তর) এবং প্রোটিনের গতিশীল মোজাইক দ্বারা গঠিত।</div>
            </section>

            <section class="section" id="organelles">
                <h2>৪. সাইটোপ্লাজমীয় অঙ্গাণুসমূহ (Cytoplasmic Organelles)</h2>
                <ul>
                    <li><b>মাইটোকন্ড্রিয়া (Mitochondria):</b> কোষের ক্রেবস চক্র, ইলেকট্রন ট্রান্সপোর্ট সিস্টেম সম্পন্ন হয় এবং শক্তি উৎপাদিত হয় বলে একে কোষের <b>'পাওয়ার হাউস' (Power House)</b> বলা হয়। এর ভেতর ক্রিষ্টি ও অক্সিসোম থাকে।</li>
                    <li><b>প্লাস্টিড (Plastid):</b> উদ্ভিদ কোষের অন্যতম প্রধান অঙ্গাণু। এটি তিন প্রকার: ক্লোরোপ্লাস্ট (সবুজ বর্ণকণিকা, খাদ্য তৈরি করে), ক্রোমোপ্লাস্ট (রঙিন অংশ যেমন ফুল ও ফলে থাকে) ও লিউকোপ্লাস্ট (বর্ণহীন)।</li>
                    <li><b>রাইবোজোম (Ribosome):</b> প্রোটিন সংশ্লেষণের প্রধান কেন্দ্র। একে কোষের <b>'প্রোটিন ফ্যাক্টরি'</b> বলা হয়।</li>
                    <li><b>এন্ডোপ্লাজমিক জালিকা (ER):</b> নিউক্লীয় ঝিল্লি থেকে প্লাজমা ঝিল্লি পর্যন্ত বিস্তৃত জালিকাকার অঙ্গাণু। গায়ে রাইবোজোম থাকলে অমসৃণ (Rough ER) এবং না থাকলে মসৃণ (Smooth ER) বলে।</li>
                    <li><b>গলগি বডি (Golgi Body / Apparatus):</b> সিস্টারনি, ভেসিকল ও ভ্যাকুওল নিয়ে গঠিত; প্রোটিন প্যাকিং ও হরমোন/এনজাইম ক্ষরণে সাহায্য করে।</li>
                    <li><b>লাইসোসোম (Lysosome):</b> হাইড্রোলাইটিক বা পাচক এনজাইমে পূর্ণ থাকে। প্রতিকূল পরিস্থিতিতে বা অঙ্গাণু ধ্বংস করতে নিজের কোষকেই ভক্ষণ করে বলে একে <b>'আত্মঘাতী থলি' (Suicide Bag)</b> বলা হয়।</li>
                    <li><b>সেন্ট্রোসোম (Centrosome):</b> দুটি সেন্ট্রিওল ও সেন্ট্রোস্ফিয়ার নিয়ে গঠিত। প্রাণী কোষে কোষ বিভাজনের সময় স্পিন্ডল তন্তু তৈরি করে।</li>
                </ul>
            </section>

            <section class="section" id="nucleus">
                <h2>৫. নিউক্লিয়াস ও ক্রোমোজোম</h2>
                <h3>নিউক্লিয়াস (Nucleus)</h3>
                <p>প্রোটোপ্লাজমের সবচেয়ে ঘন, পর্দাঘেরা ও সুনির্দিষ্ট অঙ্গাণু যা কোষের সকল জৈবিক কার্যাবলী নিয়ন্ত্রণ করে। একটি আদর্শ নিউক্লিয়াসের ৪টি অংশ:</p>
                <ol>
                    <li><b>নিউক্লিয়ার ঝিল্লি:</b> দ্বি-স্তর বিশিষ্ট ও ছিদ্রযুক্ত আবরণী।</li>
                    <li><b>নিউক্লিওপ্লাজম:</b> নিউক্লিয়াসের ভেতরে থাকা স্বচ্ছ ও জেলি সদৃশ তরল।</li>
                    <li><b>নিউক্লিওলাস:</b> নিউক্লিয়াসের ভেতর ঘন ও গোলাকার বস্তু যা RNA ও প্রোটিন তৈরি করে।</li>
                    <li><b>ক্রোমাটিন জালিকা:</b> DNA ও হিস্টোন প্রোটিনের সমন্বয়ে গঠিত সূত্রাকার অংশ, যা বংশগতির বৈশিষ্ট্য বহন করে।</li>
                </ol>
                <h3>ক্রোমোজোম (Chromosome)</h3>
                <p>নিউক্লিওপ্রোটিন দ্বারা গঠিত যে তন্তু সদৃশ অংশ বংশগতীয় বৈশিষ্ট্য এক প্রজন্ম থেকে অন্য প্রজন্মে বহন করে তাকে ক্রোমোজোম বলে। ক্রোমোজোমের প্রধান অংশগুলো হলো ক্রোমাটাইড, সেন্ট্রোমিয়ার, কাইনেটোকোর ও টেলোমিয়ার। সেন্ট্রোমিয়ারের অবস্থানের ওপর ভিত্তি করে ক্রোমোজোম চার প্রকার: মেটাসেন্ট্রিক, সাব-মেটাসেন্ট্রিক, এক্রোসেন্ট্রিক ও টেলোসেন্ট্রিক।</p>
            </section>

            <section class="section" id="practice">
                <h2>৬. অনুশীলনী (Practice Questions)</h2>
                <ol>
                    <li>কোষ তত্ত্ব বলতে কী বোঝো? কোষ আবিষ্কারের ইতিহাস সংক্ষেপে লেখো।</li>
                    <li>প্রাককেন্দ্রিক ও ইউক্যারিওটিক কোষের মধ্যে পার্থক্য নিরূপণ করো।</li>
                    <li>ফ্লুইড মোজাইক মডেলের চিত্রসহ বর্ণনা দাও।</li>
                    <li>মাইটোকন্ড্রিয়াকে কেন কোষের 'পাওয়ার হাউস' বলা হয়?</li>
                    <li>লাইসোসোমকে 'আত্মঘাতী থলি' বলা হয় কেন ব্যাখ্যা করো।</li>
                    <li>নিউক্লিয়াসের চিহ্নিত চিত্র এঁকে এর বিভিন্ন অংশের বর্ণনা দাও।</li>
                </ol>
            </section>

            <section class="section" id="quiz">
                <h2>৭. MCQ পরীক্ষা (Interactive Quiz)</h2>
                <div class="quiz" data-answer="b">
                    <p>১. ফ্লুইড মোজাইক মডেল কে প্রদান করেন?</p><label><input type="radio" name="q1" value="a"> রবার্ট হুক ও ল্যুবেনহুক</label><label><input type="radio" name="q1" value="b"> সিঙ্গার ও নিকলসন</label><label><input type="radio" name="q1" value="c"> স্লেইডেন ও শোয়ান</label><label><input type="radio" name="q1" value="d"> ওয়াটসন ও ক্রিক</label><button type="button" onclick="checkQuiz(this)">উত্তর যাচাই</button>
                    <div class="feedback" aria-live="polite"></div>
                </div>

                <div class="quiz" data-answer="c">
                    <p>২. নিচের কোনটিকে কোষের 'আত্মঘাতী থলি' বলা হয়?</p><label><input type="radio" name="q2" value="a"> মাইটোকন্ড্রিয়া</label><label><input type="radio" name="q2" value="b"> রাইবোজোম</label><label><input type="radio" name="q2" value="c"> লাইসোসোম</label><label><input type="radio" name="q2" value="d"> গলগি বডি</label><button type="button" onclick="checkQuiz(this)">উত্তর যাচাই</button>
                    <div class="feedback" aria-live="polite"></div>
                </div>

                <div class="quiz" data-answer="a">
                    <p>৩. প্রাককেন্দ্রিক কোষে কোন ধরনের রাইবোজোম থাকে?</p><label><input type="radio" name="q3" value="a"> 70S</label><label><input type="radio" name="q3" value="b"> 80S</label><label><input type="radio" name="q3" value="c"> 60S</label><label><input type="radio" name="q3" value="d"> 40S</label><button type="button" onclick="checkQuiz(this)">উত্তর যাচাই</button>
                    <div class="feedback" aria-live="polite"></div>
                </div>
            </section>

        </main>
    </div>

    <footer>© <?= $currentYear ?> • HSC Biology 1st Paper • Chapter 1: Cell and its Structure</footer>
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
                feedback.textContent = 'এটি সঠিক নয়। আবার চেষ্টা করো।';
                feedback.className = 'feedback incorrect';
            }
        }
    </script>
</body>

</html>