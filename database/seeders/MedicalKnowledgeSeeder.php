<?php

namespace Database\Seeders;

use App\Models\MedicalKnowledgeArticle;
use Illuminate\Database\Seeder;

class MedicalKnowledgeSeeder extends Seeder
{
    /**
     * Candidate articles only. None are clinically approved.
     */
    public function run(): void
    {
        foreach ($this->articles() as $article) {
            MedicalKnowledgeArticle::query()->firstOrCreate(
                [
                    'topic' => $article['topic'],
                    'language' => $article['language'],
                ],
                $article,
            );
        }
    }

    /**
     * @return list<array{title: string, topic: string, language: string, content: string, source_url: string, source_name: string, approval_status: string, version: int, is_active: bool}>
     */
    private function articles(): array
    {
        return [
            $this->candidate(
                'fever',
                'en',
                'Fever in adults',
                'A high temperature in adults is usually 38°C or above. The NHS says rest and plenty of fluids, with water as the best choice, are the usual self-care, and that some adults use paracetamol or ibuprofen when those medicines are suitable for them. This article does not decide whether a medicine is safe for you or what dose to take. Check the packet leaflet or ask a pharmacist. Stay at home while you have a high temperature. Seek urgent care if it is not improving, is getting worse, or you feel seriously unwell. A fever has many causes. This is not a diagnosis.',
                'https://www.nhs.uk/symptoms/fever-in-adults/',
                'NHS',
            ),
            $this->candidate(
                'fever',
                'bn',
                'প্রাপ্তবয়স্কদের জ্বর',
                'প্রাপ্তবয়স্কদের উচ্চ তাপমাত্রা সাধারণত ৩৮ ডিগ্রি সেলসিয়াস বা তার বেশি। NHS বিশ্রাম ও পর্যাপ্ত তরল, বিশেষ করে পানি, বলছে। কোনো ওষুধ আপনার জন্য নিরাপদ কি না বা কত মাত্রা নেবেন, এই নিবন্ধ তা ঠিক করে না। প্যাকেটের তথ্য পড়ুন বা ফার্মাসিস্টকে জিজ্ঞাসা করুন। জ্বর না কমলে, বাড়লে, বা আপনি গুরুতর অসুস্থ বোধ করলে জরুরি সেবা নিন। এটি রোগ নির্ণয় নয়।',
                'https://www.nhs.uk/symptoms/fever-in-adults/',
                'NHS',
            ),
            $this->candidate(
                'hydration',
                'en',
                'Everyday hydration',
                'NHS Eatwell guidance says most people can use pale yellow pee as a sign they are drinking enough, and gives 6 to 8 cups or glasses of fluid a day as a guide. You may need more in hot weather, during long activity, in pregnancy or while breastfeeding, or when you are ill. Water is a healthy choice. This article does not set a personal fluid target.',
                'https://www.nhs.uk/live-well/eat-well/food-guidelines-and-food-labels/water-drinks-nutrition/',
                'NHS',
            ),
            $this->candidate(
                'hydration',
                'bn',
                'প্রতিদিনের পানি পান',
                'NHS-এর নির্দেশনায় বেশিরভাগ মানুষের জন্য দিনে প্রায় ৬ থেকে ৮ কাপ তরল একটি সাধারণ ধারণা, এবং হালকা হলুদ প্রস্রাব যথেষ্ট পানির একটি লক্ষণ হতে পারে। গরম, দীর্ঘ পরিশ্রম, গর্ভাবস্থা বা অসুস্থতায় আরও তরল লাগতে পারে। পানি একটি ভালো পছন্দ। এটি আপনার ব্যক্তিগত লক্ষ্য নির্ধারণ করে না।',
                'https://www.nhs.uk/live-well/eat-well/food-guidelines-and-food-labels/water-drinks-nutrition/',
                'NHS',
            ),
            $this->candidate(
                'cough',
                'en',
                'Cough',
                'The NHS says a cough will often clear within 3 to 4 weeks. Rest and plenty of fluids are the usual self-care. See a GP if a cough lasts more than 3 weeks, you are losing weight without trying, or your immune system is weakened. Get urgent help for chest pain, difficulty breathing, coughing up blood, or a cough that is severe or quickly worse. This article does not identify the cause of a cough.',
                'https://www.nhs.uk/symptoms/cough/',
                'NHS',
            ),
            $this->candidate(
                'cough',
                'bn',
                'কাশি',
                'NHS বলছে কাশি প্রায়ই ৩ থেকে ৪ সপ্তাহের মধ্যে কমে। বিশ্রাম ও পর্যাপ্ত তরল সাধারণ যত্ন। ৩ সপ্তাহের বেশি কাশি, বিনা কারণে ওজন কমা, বা দুর্বল রোগপ্রতিরোধ ক্ষমতা থাকলে চিকিৎসককে দেখান। বুকে ব্যথা, শ্বাসকষ্ট, রক্তসহ কাশি, বা দ্রুত খারাপ হওয়া কাশিতে জরুরি সাহায্য নিন। এটি কাশির কারণ নির্ণয় করে না।',
                'https://www.nhs.uk/symptoms/cough/',
                'NHS',
            ),
            $this->candidate(
                'sore_throat',
                'en',
                'Sore throat',
                'The NHS says most sore throats get better on their own. Fluids, cool or soft food, rest, and a warm salt-water gargle for adults may ease discomfort. Children should not gargle salt water, and young children should not be given small hard sweets because of choking. Antibiotics are not usually needed. Ask a pharmacist before using a pain-relief medicine, because some are not safe for everyone. Get urgent help if breathing or swallowing is difficult, if there is drooling, or if symptoms are severe and worsening quickly.',
                'https://www.nhs.uk/symptoms/sore-throat/',
                'NHS',
            ),
            $this->candidate(
                'sore_throat',
                'bn',
                'গলা ব্যথা',
                'NHS বলছে বেশিরভাগ গলা ব্যথা নিজেই কমে। তরল, নরম বা ঠান্ডা খাবার ও বিশ্রাম সাহায্য করতে পারে। প্রাপ্তবয়স্করা হালকা গরম লবণপানি দিয়ে কুলি করতে পারেন। শিশুদের কুলি করানো উচিত নয়, এবং ছোট শিশুদের শক্ত মিষ্টি দেবেন না কারণ শ্বাসনালীতে আটকে যেতে পারে। অ্যান্টিবায়োটিক সাধারণত দরকার হয় না। ব্যথার ওষুধের আগে ফার্মাসিস্টকে জিজ্ঞাসা করুন। শ্বাস নিতে বা গিলতে কষ্ট, লালা গড়ানো, বা দ্রুত খারাপ হলে জরুরি সাহায্য নিন।',
                'https://www.nhs.uk/symptoms/sore-throat/',
                'NHS',
            ),
            $this->candidate(
                'headache',
                'en',
                'Headache',
                'The NHS says common headache self-care includes drinking water, resting when you also have a cold or flu, and not skipping meals. Stress, dehydration, alcohol, and eyestrain are among common triggers. Get urgent help for a sudden very severe headache, a headache with fever and neck stiffness, confusion, weakness, or a headache after a head injury. This article does not diagnose migraine or any other cause, and it does not choose a painkiller or a dose.',
                'https://www.nhs.uk/symptoms/headaches/',
                'NHS',
            ),
            $this->candidate(
                'headache',
                'bn',
                'মাথাব্যথা',
                'NHS বলছে পানি পান, ঠান্ডা বা ফ্লু থাকলে বিশ্রাম, এবং খাবার বাদ না দেওয়া সাধারণ যত্ন। মানসিক চাপ, পানিশূন্যতা, অ্যালকোহল ও চোখের পরিশ্রম সাধারণ কারণ হতে পারে। হঠাৎ খুব তীব্র মাথাব্যথা, জ্বরের সাথে ঘাড় শক্ত হওয়া, বিভ্রান্তি, দুর্বলতা, বা মাথায় আঘাতের পর মাথাব্যথায় জরুরি সাহায্য নিন। এটি মাইগ্রেন বা অন্য কারণ নির্ণয় করে না এবং কোনো ব্যথানাশক বা মাত্রা বেছে দেয় না।',
                'https://www.nhs.uk/symptoms/headaches/',
                'NHS',
            ),
            $this->candidate(
                'medicine_safety',
                'en',
                'Medicine safety',
                'WHO warns that substandard and falsified medical products are a worldwide problem and can fail to treat illness or cause harm. Use medicines from a licensed pharmacy, check the packaging, and read the leaflet. Do not use someone else\'s prescription medicine. This article does not recommend a product, a dose, or a treatment for an individual. Ask a pharmacist or doctor before starting, stopping, or combining medicines.',
                'https://www.who.int/news-room/fact-sheets/detail/substandard-and-falsified-medical-products',
                'World Health Organization',
            ),
            $this->candidate(
                'medicine_safety',
                'bn',
                'ওষুধের নিরাপত্তা',
                'WHO সতর্ক করে যে নিম্নমানের ও ভেজাল চিকিৎসা পণ্য বিশ্বজুড়ে ক্ষতি করতে পারে। লাইসেন্সকৃত ফার্মেসি থেকে ওষুধ নিন, মোড়ক দেখুন এবং লিফলেট পড়ুন। অন্যের প্রেসক্রিপশনের ওষুধ ব্যবহার করবেন না। এই নিবন্ধ কোনো ব্যক্তির জন্য পণ্য, মাত্রা বা চিকিৎসা সুপারিশ করে না। ওষুধ শুরু, বন্ধ বা একসাথে নেওয়ার আগে ফার্মাসিস্ট বা চিকিৎসককে জিজ্ঞাসা করুন।',
                'https://www.who.int/news-room/fact-sheets/detail/substandard-and-falsified-medical-products',
                'World Health Organization',
            ),
            $this->candidate(
                'dehydration',
                'en',
                'Dehydration',
                'The NHS describes dehydration as losing more fluid than you take in. Thirst, dark strong-smelling pee, peeing less, dizziness, and a dry mouth can be signs. Drink fluids. If you are vomiting, start with small sips. A pharmacist can advise on oral rehydration solutions. Avoid alcohol. Get urgent help for confusion, cold or blotchy skin, difficulty breathing, or being hard to wake. Babies and young children need professional advice rather than this general note.',
                'https://www.nhs.uk/conditions/dehydration/',
                'NHS',
            ),
            $this->candidate(
                'dehydration',
                'bn',
                'পানিশূন্যতা',
                'NHS পানিশূন্যতাকে শরীর থেকে নেওয়া তরলের চেয়ে বেশি তরল হারানো বলে। তৃষ্ণা, গাঢ় দুর্গন্ধযুক্ত প্রস্রাব, কম প্রস্রাব, মাথা ঘোরা ও মুখ শুকানো লক্ষণ হতে পারে। তরল পান করুন। বমি হলে অল্প চুমুক দিয়ে শুরু করুন। মদ্যপান এড়িয়ে চলুন। বিভ্রান্তি, ঠান্ডা বা ছোপ ছোপ ত্বক, শ্বাসকষ্ট, বা জাগানো কঠিন হলে জরুরি সাহায্য নিন। শিশুদের জন্য চিকিৎসকের পরামর্শ দরকার।',
                'https://www.nhs.uk/conditions/dehydration/',
                'NHS',
            ),
            $this->candidate(
                'warning_signs',
                'en',
                'When to seek urgent help',
                'NHS 999 is for life-threatening emergencies such as suspected stroke, heart attack, severe difficulty breathing, or serious bleeding. If you are not sure you need 999, an urgent-care service such as NHS 111 can help you decide. This assistant cannot judge how unwell you are and cannot replace that call.',
                'https://www.nhs.uk/nhs-services/urgent-and-emergency-care-services/when-to-call-999/',
                'NHS',
            ),
            $this->candidate(
                'warning_signs',
                'bn',
                'জরুরি সতর্কতা',
                'NHS 999 জীবনঝুঁকির জরুরি অবস্থার জন্য, যেমন সম্ভাব্য স্ট্রোক, হার্ট অ্যাটাক, তীব্র শ্বাসকষ্ট বা মারাত্মক রক্তক্ষরণ। ৯৯৯ দরকার কি না নিশ্চিত না হলে NHS 111-এর মতো জরুরি সেবা সাহায্য করতে পারে। এই সহকারী আপনি কতটা অসুস্থ তা বিচার করতে পারে না এবং সেই কলের বিকল্প নয়।',
                'https://www.nhs.uk/nhs-services/urgent-and-emergency-care-services/when-to-call-999/',
                'NHS',
            ),
        ];
    }

    /**
     * @return array{title: string, topic: string, language: string, content: string, source_url: string, source_name: string, approval_status: string, version: int, is_active: bool, review_date: null, reviewer_name: null}
     */
    private function candidate(string $topic, string $language, string $title, string $content, string $url, string $source): array
    {
        return [
            'title' => $title,
            'topic' => $topic,
            'language' => $language,
            'content' => $content,
            'source_url' => $url,
            'source_name' => $source,
            'approval_status' => 'pending',
            'review_date' => null,
            'reviewer_name' => null,
            'version' => 1,
            'is_active' => true,
        ];
    }
}
