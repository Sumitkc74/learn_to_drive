<?php

namespace Database\Seeders;

use App\Models\ExamInformation;
use App\Models\Question;
use App\Models\Tutorial;
use Illuminate\Database\Seeder;

/**
 * Real, production-safe learning content for the Nepal driving licence
 * exams. Every fact here traces to a source listed in
 * ~/workspace/learn-to-drive-content/SOURCES.md.
 *
 * Idempotent: every row is created with firstOrCreate on its natural
 * unique key (question text, tutorial title, exam-information name), so
 * running this seeder repeatedly never duplicates rows. No demo markers,
 * no demo accounts, no environment restriction.
 *
 * Deliberately omitted: specific licence fees, written-exam pass marks
 * and question counts, and fine amounts. Published guides disagree on
 * those figures and they change by province and year, so the
 * ExamInformation entries below tell learners to check the current
 * DoTM notice and their province portal instead of stating a figure
 * that may be wrong. Traffic signs are also omitted: the learner signs
 * list only shows signs with an attached image, so text-only signs
 * would be invisible (a v2 item, with real sign images).
 */
class RealContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->questions() as [$question, $options, $correct, $category, $difficulty, $explanation]) {
            Question::firstOrCreate(['question' => $question], [
                'option1' => $options[0],
                'option2' => $options[1],
                'option3' => $options[2],
                'option4' => $options[3],
                'correctOption' => $correct,
                'category' => $category,
                'difficulty' => $difficulty,
                'explanation' => $explanation,
                'status' => 'Published',
            ]);
        }

        // Nepali translations of the same 44 questions, in the same order
        // and with the same correct answers, categories and difficulties.
        // Question language is auto-detected from the question text
        // (Devanagari script means Nepali), so these seed as 'ne' and the
        // English set above seeds as 'en'. No new facts are introduced:
        // every Nepali row is a translation of an English row above.
        foreach ($this->nepaliQuestions() as [$question, $options, $correct, $category, $difficulty, $explanation]) {
            Question::firstOrCreate(['question' => $question], [
                'option1' => $options[0],
                'option2' => $options[1],
                'option3' => $options[2],
                'option4' => $options[3],
                'correctOption' => $correct,
                'category' => $category,
                'difficulty' => $difficulty,
                'explanation' => $explanation,
                'status' => 'Published',
            ]);
        }

        foreach ($this->tutorials() as [$title, $description]) {
            Tutorial::firstOrCreate(['title' => $title], [
                'description' => $description,
                'videoLink' => '',
            ]);
        }

        foreach ($this->examInformation() as [$name, $description]) {
            ExamInformation::firstOrCreate(['name' => $name], [
                'description' => $description,
                'language' => 'English',
            ]);
        }
    }

    /** @return array<int, array{string, array<int, string>, string, string, string, string}> */
    private function questions(): array
    {
        return [
            // General
            ['On which side of the road must you drive in Nepal?',
                ['The left-hand side', 'The right-hand side', 'The middle of the road', 'Either side, as you prefer'], 'A', 'General', 'Easy',
                'Nepal is a left-hand traffic country, so you keep left and normally overtake on the right.'],
            ['Which authority issues driving licences in Nepal?',
                ['The Department of Transport Management (DoTM)', 'Nepal Traffic Police', 'Your local municipality', 'The Department of Roads'], 'A', 'General', 'Easy',
                'DoTM handles licensing and registration; Traffic Police handle on-road enforcement.'],
            ['Which document proves you are licensed to drive a vehicle?',
                ['A valid driving licence for that vehicle category', 'A citizenship certificate alone', 'A vehicle bluebook alone', 'An insurance paper alone'], 'A', 'General', 'Easy',
                'Only a valid licence for the matching category authorises you to drive that vehicle.'],
            ['What is the minimum age for a Category A licence (motorcycle or scooter) in Nepal?',
                ['16 years', '18 years', '21 years', '15 years'], 'A', 'General', 'Easy',
                'Category A covers motorcycles and scooters and can be applied for at 16.'],
            ['What is the minimum age for a Category B licence (car, jeep or van) in Nepal?',
                ['16 years', '18 years', '21 years', '25 years'], 'B', 'General', 'Easy',
                'Category B covers cars, jeeps and vans and requires you to be 18.'],
            ['What form does the written licence exam (Likhit) take in Nepal?',
                ['Computer-based multiple-choice questions, in Nepali or English', 'A handwritten essay exam', 'An oral exam only', 'A practical driving test only'], 'A', 'General', 'Easy',
                'The written exam is computer-based, with four options per question, and you choose Nepali or English.'],
            ['After passing the written exam, what is the next step to get a licence?',
                ['Take the practical trial (driving) test', 'Pay the final fee and collect the card at once', 'Retake the written exam', 'Wait one year'], 'A', 'General', 'Easy',
                'The written pass qualifies you for the trial; the licence is issued only after you pass it.'],
            ['What score is needed to pass the practical trial test in Nepal?',
                ['50 out of 100', '60 out of 100', '70 out of 100', '80 out of 100'], 'C', 'General', 'Medium',
                'Under the point-based trial system, candidates start at 100 and must finish with 70 or more.'],
            ['How many trial attempts do you get on one written-exam pass?',
                ['One attempt', 'Two attempts', 'Three attempts', 'Five attempts'], 'C', 'General', 'Medium',
                'You get three trial attempts on one written pass; failing all three means starting the process again.'],
            ['What does your licence category control?',
                ['Which types of vehicle you are allowed to drive', 'How fast you may drive', 'Which roads you may use', 'How many passengers you may carry'], 'A', 'General', 'Easy',
                'A category authorises a vehicle class; driving another class without it is unlicensed driving.'],
            // Road Signs
            ['What does a red-bordered triangular road sign mean?',
                ['Warning: a hazard lies ahead', 'A rule you must obey', 'Information about a place', 'Parking is allowed here'], 'A', 'Road Signs', 'Easy',
                'Triangular signs with a red border warn of hazards ahead, such as a bend or a crossing.'],
            ['What does a red-bordered circular road sign mean?',
                ['Something is prohibited', 'Something is mandatory', 'A place to stop for rest', 'A warning of a hazard'], 'A', 'Road Signs', 'Easy',
                'A red-bordered circle gives a prohibition, for example no entry or no overtaking.'],
            ['What does a blue circular road sign mean?',
                ['An instruction you must follow', 'Something is prohibited', 'A warning of a hazard', 'A tourist attraction'], 'A', 'Road Signs', 'Medium',
                'Blue circles give mandatory instructions, such as keep left or turn in the shown direction.'],
            ['What must you do at a red octagonal STOP sign?',
                ['Come to a complete stop, then proceed only when it is safe', 'Slow down but keep rolling', 'Stop only if other traffic is coming', 'Sound your horn and proceed'], 'A', 'Road Signs', 'Easy',
                'STOP means a full stop at the line, not just slowing down, before you move off safely.'],
            ['What does an upside-down triangular sign (point at the bottom, red border) mean?',
                ['Give way to traffic on the road you are joining', 'Stop completely', 'No entry', 'Road works ahead'], 'A', 'Road Signs', 'Medium',
                'The inverted triangle is the give-way sign: yield to traffic that has priority.'],
            ['What does a sign showing a pedestrian on a striped crossing mean?',
                ['A pedestrian crossing is ahead', 'Pedestrians are prohibited here', 'A school is nearby', 'Parking for pedestrians'], 'A', 'Road Signs', 'Easy',
                'It warns you a zebra crossing is coming, so slow down and be ready to stop.'],
            ['What does a blue square sign with a white letter P mean?',
                ['Parking is allowed here', 'Petrol station ahead', 'Police checkpoint ahead', 'Pedestrians only'], 'A', 'Road Signs', 'Easy',
                'The P sign marks a place where parking is permitted.'],
            ['What does a sign showing a horn with a red line through it mean?',
                ['Do not sound your horn here', 'Sound your horn to warn others', 'Horn testing area', 'Horn repair ahead'], 'A', 'Road Signs', 'Medium',
                'The crossed-out horn is a no-horn zone, used near hospitals, schools and similar places.'],
            // Traffic Rules
            ['What does a red traffic light mean?',
                ['Stop and wait behind the stop line', 'Proceed with caution', 'Slow down and keep going', 'Turn left only'], 'A', 'Traffic Rules', 'Easy',
                'Red means stop. You must wait behind the stop line until the light changes.'],
            ['What does an amber (yellow) traffic light mean?',
                ['Speed up to beat the red light', 'Prepare to stop; do not enter unless it is unsafe to stop', 'Proceed freely', 'Only left turns are allowed'], 'B', 'Traffic Rules', 'Medium',
                'Amber warns the light is about to turn red, so you prepare to stop rather than rushing through.'],
            ['What does a green traffic light mean?',
                ['Proceed, but only if the way ahead is clear', 'Proceed at any speed', 'Stop and wait', 'Turn around'], 'A', 'Traffic Rules', 'Easy',
                'Green means you may go, but you must still check the way is clear before moving off.'],
            ['What must you do when a pedestrian is already crossing at a zebra crossing?',
                ['Stop and let them cross', 'Drive around them slowly', 'Sound your horn and proceed', 'Overtake them on the crossing'], 'A', 'Traffic Rules', 'Easy',
                'Pedestrians on a crossing have priority; you must stop and wait until they have crossed.'],
            ['On which side do you normally overtake another vehicle in Nepal?',
                ['On the left', 'On the right', 'On either side', 'On the shoulder'], 'B', 'Traffic Rules', 'Easy',
                'Because Nepal keeps left, overtaking is normally done on the right, when it is safe.'],
            ['When must you use your indicators?',
                ['Before turning or changing lanes', 'Only at night', 'Only on highways', 'Only when traffic police are present'], 'A', 'Traffic Rules', 'Easy',
                'Signal in good time before every turn and lane change so other road users know your intention.'],
            ['When must your headlights be on?',
                ['At night and in poor visibility, such as rain, fog or tunnels', 'Only on highways', 'Only when it is raining', 'Only after midnight'], 'A', 'Traffic Rules', 'Easy',
                'Headlights are for seeing and being seen whenever daylight is not enough.'],
            ['What must you do when an emergency vehicle with sirens approaches?',
                ['Pull to the side safely and let it pass', 'Speed up to stay ahead of it', 'Follow closely behind it', 'Stop in the middle of the road'], 'A', 'Traffic Rules', 'Easy',
                'Emergency vehicles on call have priority; move aside safely and do not block them.'],
            ['When turning right across oncoming traffic, what must you do?',
                ['Give way to oncoming traffic and turn only when it is safe', 'Turn immediately; oncoming traffic must stop', 'Sound your horn and turn', 'Turn from the left lane'], 'A', 'Traffic Rules', 'Medium',
                'A right turn across traffic never has priority over vehicles coming towards you.'],
            ['What is the rule about driving after drinking alcohol?',
                ['Never drive after drinking', 'One drink is always safe', 'It is fine for short distances', 'It is fine if you drive slowly'], 'A', 'Traffic Rules', 'Easy',
                'Drink driving is one of the most seriously treated offences and a major cause of crashes.'],
            ['What is the rule about using a hand-held mobile phone while driving?',
                ['Do not use one; it distracts you from the road', 'It is fine in slow traffic', 'It is fine if you hold it low', 'It is fine on a straight road'], 'A', 'Traffic Rules', 'Easy',
                'A hand-held phone takes your eyes, hands and attention off driving and is not permitted.'],
            ['Who must wear a seatbelt in a car?',
                ['The driver and the front-seat passengers', 'Only the driver', 'Only passengers', 'Nobody, it is optional'], 'A', 'Traffic Rules', 'Easy',
                'Seatbelts are mandatory for the driver and front-seat passengers on every trip.'],
            // Road Safety
            ['Why must a motorcycle rider wear a helmet?',
                ['It protects the head in a crash, the most serious injury risk', 'It keeps the wind out of your eyes only', 'It is needed only on highways', 'It is needed only at night'], 'A', 'Road Safety', 'Easy',
                'A helmet is the single most effective protection a rider has in a crash.'],
            ['What is a safe following distance?',
                ['A gap large enough to stop safely, made larger in rain or poor grip', 'One car length at any speed', 'Half a car length in traffic', 'No gap is needed if you brake quickly'], 'A', 'Road Safety', 'Medium',
                'The faster you go, and the worse the grip, the longer your stopping distance becomes.'],
            ['Why should you slow down in rain?',
                ['Wet roads reduce grip and lengthen stopping distances', 'Rain damages the engine', 'Wipers use too much fuel', 'Other drivers slow down anyway'], 'A', 'Road Safety', 'Easy',
                'Water between tyre and road cuts grip, so braking takes longer and skidding is more likely.'],
            ['What should you do first if your brakes fail?',
                ['Pump the brake pedal, change to a lower gear, and steer to a safe place to stop', 'Switch the engine off at speed', 'Pull the handbrake hard at once', 'Keep accelerating to reach a workshop'], 'A', 'Road Safety', 'Hard',
                'Pumping may restore pressure, engine braking slows you, and a hard handbrake pull can lock the wheels.'],
            ['What should you do if a tyre bursts while driving?',
                ['Hold the steering firmly, ease off the accelerator, and slow down gradually', 'Brake hard immediately', 'Steer sharply to the roadside', 'Accelerate to keep control'], 'A', 'Road Safety', 'Hard',
                'Sudden braking or steering after a burst can throw the vehicle out of control.'],
            ['What should you check before a long journey?',
                ['Tyres, brakes, lights, fluid levels and fuel', 'Only the fuel gauge', 'Only the mirrors', 'Nothing, if the vehicle started fine'], 'A', 'Road Safety', 'Easy',
                'A short pre-journey check catches the faults that cause most breakdowns and crashes.'],
            ['What should you do if you feel drowsy while driving?',
                ['Stop somewhere safe and rest before continuing', 'Open a window and keep driving', 'Drive faster to arrive sooner', 'Drink tea and keep going at speed'], 'A', 'Road Safety', 'Easy',
                'Drowsiness slows reactions as much as drink does; fresh air does not replace rest.'],
            ['What should you do if oncoming headlights dazzle you at night?',
                ['Look towards the left edge of the road and slow down', 'Stare at the lights', 'Switch on your full beam', 'Close your eyes briefly'], 'A', 'Road Safety', 'Medium',
                'Looking at the road edge keeps you in lane while your eyes recover from the glare.'],
            // Vehicle Knowledge
            ['What does a red oil-pressure warning light mean?',
                ['Stop safely as soon as you can and check the oil; do not keep driving', 'Top up the oil at the next fuel station, whenever that is', 'The oil needs changing next month', 'The warning is only for diesel vehicles'], 'A', 'Vehicle Knowledge', 'Medium',
                'Loss of oil pressure can seize the engine within minutes, so stop promptly and safely.'],
            ['What does an engine temperature warning light mean?',
                ['The engine is overheating; stop safely and let it cool', 'The heater is on', 'The coolant needs topping up next week', 'The fan belt is loose only'], 'A', 'Vehicle Knowledge', 'Medium',
                'Driving on with an overheating engine causes serious, expensive damage.'],
            ['Why does correct tyre pressure matter?',
                ['Under-inflated tyres harm handling, wear out faster and use more fuel', 'It only affects ride comfort', 'It only matters for long journeys', 'Pressure has no effect on safety'], 'A', 'Vehicle Knowledge', 'Medium',
                'Wrong pressure changes grip and braking and can lead to a blowout.'],
            ['What is engine braking?',
                ['Using a lower gear to slow the vehicle, especially useful downhill', 'Braking with the engine switched off', 'Using the handbrake at speed', 'Braking only with the front brakes'], 'A', 'Vehicle Knowledge', 'Hard',
                'A lower gear holds speed down on descents without overheating the brakes.'],
            ['Why must you check mirrors and the blind spot before changing lanes?',
                ['A vehicle beside you may be hidden from the mirrors alone', 'It is only a courtesy to other drivers', 'Mirrors are legally optional', 'Blind spots only exist in large vehicles'], 'A', 'Vehicle Knowledge', 'Easy',
                'Every vehicle has a blind spot beside it that mirrors do not cover.'],
            ['What is the handbrake (parking brake) for?',
                ['Holding the vehicle stationary, especially on a slope', 'Stopping quickly at speed', 'Replacing the foot brake', 'Locking the steering'], 'A', 'Vehicle Knowledge', 'Easy',
                'The handbrake holds the parked vehicle; it is not a substitute for the foot brake at speed.'],
        ];
    }

    /** @return array<int, array{string, array<int, string>, string, string, string, string}> */
    private function nepaliQuestions(): array
    {
        return [
            // General
            ['नेपालमा सडकको कुन तर्फ गाडी चलाउनुपर्छ?',
                ['बायाँतर्फ', 'दायाँतर्फ', 'सडकको बीचमा', 'आफ्नो रोजाइ अनुसार जुनसुकै तर्फ'], 'A', 'General', 'Easy',
                'नेपालमा बायाँतर्फ गाडी चलाइन्छ, त्यसैले बायाँ बस्नुहोस् र सामान्यतया दायाँबाट ओभरटेक गर्नुहोस्।'],
            ['नेपालमा सवारी चालक अनुमतिपत्र (लाइसेन्स) कुन निकायले जारी गर्छ?',
                ['यातायात व्यवस्था विभाग (DoTM)', 'नेपाल ट्राफिक प्रहरी', 'आफ्नो स्थानीय नगरपालिका', 'सडक विभाग'], 'A', 'General', 'Easy',
                'लाइसेन्स र दर्ता DoTM ले हेर्छ; सडकमा नियम पालना गराउने काम ट्राफिक प्रहरीको हो।'],
            ['तपाईंसँग गाडी चलाउने अनुमति छ भन्ने कुन कागजातले प्रमाणित गर्छ?',
                ['उक्त सवारीको श्रेणीको मान्य लाइसेन्स', 'नागरिकता प्रमाणपत्र मात्र', 'सवारीको ब्लुबुक मात्र', 'बीमाको कागज मात्र'], 'A', 'General', 'Easy',
                'मिल्दो श्रेणीको मान्य लाइसेन्सले मात्र उक्त सवारी चलाउन अनुमति दिन्छ।'],
            ['नेपालमा श्रेणी ए (मोटरसाइकल वा स्कुटर) को लाइसेन्सका लागि न्यूनतम उमेर कति हो?',
                ['16 वर्ष', '18 वर्ष', '21 वर्ष', '15 वर्ष'], 'A', 'General', 'Easy',
                'श्रेणी ए मोटरसाइकल र स्कुटरका लागि हो र 16 वर्षमा आवेदन दिन सकिन्छ।'],
            ['नेपालमा श्रेणी बी (कार, जीप वा भ्यान) को लाइसेन्सका लागि न्यूनतम उमेर कति हो?',
                ['16 वर्ष', '18 वर्ष', '21 वर्ष', '25 वर्ष'], 'B', 'General', 'Easy',
                'श्रेणी बी कार, जीप र भ्यानका लागि हो र यसका लागि 18 वर्ष पुगेको हुनुपर्छ।'],
            ['नेपालमा लिखित लाइसेन्स परीक्षा (लिखित) कस्तो हुन्छ?',
                ['कम्प्युटरमा आधारित बहुविकल्पीय प्रश्नहरू, नेपाली वा अङ्ग्रेजीमा', 'हस्तलिखित निबन्ध परीक्षा', 'मौखिक परीक्षा मात्र', 'प्रयोगात्मक परीक्षा मात्र'], 'A', 'General', 'Easy',
                'लिखित परीक्षा कम्प्युटरमा हुन्छ, प्रत्येक प्रश्नमा चार विकल्प हुन्छन्, र तपाईं नेपाली वा अङ्ग्रेजी रोज्न सक्नुहुन्छ।'],
            ['लिखित परीक्षा पास गरेपछि लाइसेन्स पाउन अर्को चरण के हो?',
                ['प्रयोगात्मक (ट्रायल) परीक्षा दिने', 'अन्तिम शुल्क तिरेर तुरुन्तै कार्ड लिने', 'लिखित परीक्षा फेरि दिने', 'एक वर्ष पर्खने'], 'A', 'General', 'Easy',
                'लिखित पास भएपछि ट्रायल दिन पाइन्छ; लाइसेन्स ट्रायल पास गरेपछि मात्र दिइन्छ।'],
            ['नेपालमा प्रयोगात्मक (ट्रायल) परीक्षा पास गर्न कति अङ्क चाहिन्छ?',
                ['100 मा 50', '100 मा 60', '100 मा 70', '100 मा 80'], 'C', 'General', 'Medium',
                'अङ्कमा आधारित ट्रायल प्रणालीमा तपाईं 100 अङ्कबाट सुरु गर्नुहुन्छ र पास हुन 70 वा सोभन्दा बढी अङ्क ल्याउनुपर्छ।'],
            ['एउटा लिखित पासमा कतिवटा ट्रायल प्रयास पाइन्छ?',
                ['एक प्रयास', 'दुई प्रयास', 'तीन प्रयास', 'पाँच प्रयास'], 'C', 'General', 'Medium',
                'एउटा लिखित पासमा तीन ट्रायल प्रयास पाइन्छ; तीनै प्रयासमा असफल भएमा प्रक्रिया फेरि सुरु गर्नुपर्छ।'],
            ['तपाईंको लाइसेन्सको श्रेणीले के निर्धारण गर्छ?',
                ['कुन प्रकारका सवारी चलाउन पाइन्छ', 'कति छिटो चलाउन पाइन्छ', 'कुन सडक प्रयोग गर्न पाइन्छ', 'कतिजना यात्रु बोक्न पाइन्छ'], 'A', 'General', 'Easy',
                'श्रेणीले सवारीको वर्ग निर्धारण गर्छ; त्यसबाहेकको वर्गको सवारी चलाउनु बिना लाइसेन्स गाडी चलाउनु हो।'],
            // Road Signs
            ['रातो किनारा भएको त्रिकोणाकार सडक चिन्हको अर्थ के हो?',
                ['चेतावनी: अगाडि खतरा छ', 'पालना गर्नुपर्ने नियम', 'स्थानबारे जानकारी', 'यहाँ पार्किङ गर्न पाइन्छ'], 'A', 'Road Signs', 'Easy',
                'रातो किनारा भएको त्रिकोणाकार चिन्हले अगाडि मोड वा क्रसिङजस्ता खतराबारे चेतावनी दिन्छ।'],
            ['रातो किनारा भएको गोलाकार सडक चिन्हको अर्थ के हो?',
                ['केही निषेध गरिएको छ', 'केही अनिवार्य छ', 'विश्राम गर्ने ठाउँ', 'खतराको चेतावनी'], 'A', 'Road Signs', 'Easy',
                'रातो किनारा भएको गोलो चिन्हले निषेध जनाउँछ, जस्तै प्रवेश निषेध वा ओभरटेक निषेध।'],
            ['नीलो गोलाकार सडक चिन्हको अर्थ के हो?',
                ['पालना गर्नुपर्ने निर्देशन', 'केही निषेध गरिएको छ', 'खतराको चेतावनी', 'पर्यटकीय स्थल'], 'A', 'Road Signs', 'Medium',
                'नीला गोलाकार चिन्हले अनिवार्य निर्देशन दिन्छ, जस्तै बायाँ जानुहोस् वा देखाइएको दिशामा मोड्नुहोस्।'],
            ['रातो अष्टभुजाकार STOP चिन्हमा के गर्नुपर्छ?',
                ['पूर्ण रूपमा रोकिनुहोस्, त्यसपछि सुरक्षित भएपछि मात्र अघि बढ्नुहोस्', 'गति घटाएर निरन्तर जानुहोस्', 'अरू सवारी आएको बेला मात्र रोकिनुहोस्', 'हर्न बजाएर अघि बढ्नुहोस्'], 'A', 'Road Signs', 'Easy',
                'STOP भनेको लाइनमा पूर्ण रूपमा रोकिनु हो, न कि गति घटाउनु मात्र; सुरक्षित भएपछि मात्र अघि बढ्नुहोस्।'],
            ['उल्टो त्रिकोणाकार चिन्ह (टुप्पो तल, रातो किनारा) को अर्थ के हो?',
                ['तपाईं जोडिने सडकको सवारीलाई बाटो दिनुहोस्', 'पूर्ण रूपमा रोकिनुहोस्', 'प्रवेश निषेध', 'अगाडि सडक निर्माण हुँदैछ'], 'A', 'Road Signs', 'Medium',
                'उल्टो त्रिकोण गिभ वे (बाटो दिने) चिन्ह हो: प्राथमिकता भएको सवारीलाई बाटो दिनुहोस्।'],
            ['धर्से भएको क्रसिङमा हिँडिरहेको पैदलयात्री देखाउने चिन्हको अर्थ के हो?',
                ['अगाडि पैदलयात्री क्रसिङ छ', 'यहाँ पैदलयात्री निषेध छ', 'नजिकै विद्यालय छ', 'पैदलयात्रीका लागि पार्किङ'], 'A', 'Road Signs', 'Easy',
                'यसले अगाडि जेब्रा क्रसिङ छ भनेर चेतावनी दिन्छ, त्यसैले गति घटाउनुहोस् र रोकिन तयार रहनुहोस्।'],
            ['सेतो P अक्षर भएको नीलो वर्गाकार चिन्हको अर्थ के हो?',
                ['यहाँ पार्किङ गर्न पाइन्छ', 'अगाडि पेट्रोल पम्प छ', 'अगाडि प्रहरी चेकपोस्ट छ', 'पैदलयात्री मात्र'], 'A', 'Road Signs', 'Easy',
                'P चिन्हले पार्किङ गर्न पाइने ठाउँ जनाउँछ।'],
            ['रातो रेखाले काटिएको हर्न देखाउने चिन्हको अर्थ के हो?',
                ['यहाँ हर्न नबजाउनुहोस्', 'अरूलाई चेतावनी दिन हर्न बजाउनुहोस्', 'हर्न परीक्षण क्षेत्र', 'अगाडि हर्न मर्मत केन्द्र'], 'A', 'Road Signs', 'Medium',
                'काटिएको हर्नले नो-हर्न क्षेत्र जनाउँछ, जुन अस्पताल, विद्यालय र यस्तै ठाउँ नजिक प्रयोग हुन्छ।'],
            // Traffic Rules
            ['रातो ट्राफिक बत्तीको अर्थ के हो?',
                ['स्टप लाइन पछाडि रोकिनुहोस् र पर्खनुहोस्', 'सावधानीपूर्वक अघि बढ्नुहोस्', 'गति घटाएर निरन्तर जानुहोस्', 'बायाँ मात्र मोड्नुहोस्'], 'A', 'Traffic Rules', 'Easy',
                'रातो भनेको रोकिनुहोस्। बत्ती नबदलिएसम्म स्टप लाइन पछाडि पर्खनुपर्छ।'],
            ['पहेँलो (एम्बर) ट्राफिक बत्तीको अर्थ के हो?',
                ['रातो हुनुअघि छिटो जानुहोस्', 'रोकिन तयार रहनुहोस्; रोकिन असुरक्षित भएमा बाहेक भित्र नपस्नुहोस्', 'स्वतन्त्र रूपमा अघि बढ्नुहोस्', 'बायाँ मोड मात्र पाइन्छ'], 'B', 'Traffic Rules', 'Medium',
                'पहेँलोले बत्ती रातो हुन लागेको चेतावनी दिन्छ, त्यसैले हतार गरेर जानुको सट्टा रोकिन तयार रहनुहोस्।'],
            ['हरियो ट्राफिक बत्तीको अर्थ के हो?',
                ['अघि बढ्नुहोस्, तर बाटो खाली छ भने मात्र', 'जुनसुकै गतिमा अघि बढ्नुहोस्', 'रोकिनुहोस् र पर्खनुहोस्', 'फर्कनुहोस्'], 'A', 'Traffic Rules', 'Easy',
                'हरियो भनेको जान पाइन्छ, तर अघि बढ्नुअघि बाटो खाली छ कि छैन जाँच गर्नुपर्छ।'],
            ['जेब्रा क्रसिङमा पैदलयात्री क्रस गरिरहेको बेला के गर्नुपर्छ?',
                ['रोकिनुहोस् र उनीहरूलाई क्रस गर्न दिनुहोस्', 'बिस्तारै उनीहरू वरपरबाट जानुहोस्', 'हर्न बजाएर अघि बढ्नुहोस्', 'क्रसिङमा उनीहरूलाई ओभरटेक गर्नुहोस्'], 'A', 'Traffic Rules', 'Easy',
                'क्रसिङमा रहेका पैदलयात्रीलाई प्राथमिकता हुन्छ; उनीहरू क्रस नगरेसम्म रोकिएर पर्खनुपर्छ।'],
            ['नेपालमा सामान्यतया अर्को सवारीलाई कुन तर्फबाट ओभरटेक गरिन्छ?',
                ['बायाँबाट', 'दायाँबाट', 'जुनसुकै तर्फबाट', 'सडकको छेउ (सोल्डर) बाट'], 'B', 'Traffic Rules', 'Easy',
                'नेपालमा बायाँतर्फ चलाइने भएकाले सुरक्षित भएमा सामान्यतया दायाँबाट ओभरटेक गरिन्छ।'],
            ['इन्डिकेटर कहिले प्रयोग गर्नुपर्छ?',
                ['मोड्नु वा लेन बदल्नुअघि', 'राति मात्र', 'राजमार्गमा मात्र', 'ट्राफिक प्रहरी उपस्थित भएमा मात्र'], 'A', 'Traffic Rules', 'Easy',
                'हरेक मोड र लेन परिवर्तनअघि समयमै सिग्नल दिनुहोस्, ताकि अन्य सडक प्रयोगकर्ताले तपाईंको मनसाय थाहा पाउनन्।'],
            ['हेडलाइट कहिले बाल्नुपर्छ?',
                ['राति र कम दृश्यतामा, जस्तै वर्षा, कुहिरो वा सुरुङमा', 'राजमार्गमा मात्र', 'वर्षा हुँदा मात्र', 'मध्यरातपछि मात्र'], 'A', 'Traffic Rules', 'Easy',
                'हेडलाइट देख्न र देखिनका लागि हो, जब दिनको उज्यालो पर्याप्त हुँदैन।'],
            ['साइरन बजाउँदै आएको आपतकालीन सवारी आउँदा के गर्नुपर्छ?',
                ['सुरक्षित रूपमा छेउमा लागेर जान दिनुहोस्', 'अगाडि रहन गति बढाउनुहोस्', 'त्यसको पछाडि नजिकै पछ्याउनुहोस्', 'सडकको बीचमा रोकिनुहोस्'], 'A', 'Traffic Rules', 'Easy',
                'बोलाइएका आपतकालीन सवारीलाई प्राथमिकता हुन्छ; सुरक्षित रूपमा छेउ लाग्नुहोस् र बाटो नछेक्नुहोस्।'],
            ['आउँदै गरेको सवारीको बाटो काटेर दायाँ मोड्दा के गर्नुपर्छ?',
                ['आउँदै गरेको सवारीलाई बाटो दिनुहोस् र सुरक्षित भएपछि मात्र मोड्नुहोस्', 'तुरुन्तै मोड्नुहोस्; आउँदै गरेको सवारी रोकिनुपर्छ', 'हर्न बजाएर मोड्नुहोस्', 'बायाँ लेनबाट मोड्नुहोस्'], 'A', 'Traffic Rules', 'Medium',
                'आउँदै गरेको सवारीको बाटो काटेर दायाँ मोड्दा तपाईंलाई कहिल्यै प्राथमिकता हुँदैन।'],
            ['रक्सी खाएर गाडी चलाउने नियम के हो?',
                ['रक्सी खाएपछि कहिल्यै गाडी नचलाउनुहोस्', 'एउटा पेय सधैं सुरक्षित हुन्छ', 'छोटो दूरीका लागि ठीक छ', 'बिस्तारै चलाएमा ठीक छ'], 'A', 'Traffic Rules', 'Easy',
                'रक्सी खाएर गाडी चलाउनु सबैभन्दा गम्भीर रूपमा लिइने कसुरमध्ये एक हो र दुर्घटनाको प्रमुख कारण हो।'],
            ['गाडी चलाउँदा हातमा मोबाइल फोन प्रयोग गर्ने नियम के हो?',
                ['प्रयोग नगर्नुहोस्; यसले सडकबाट तपाईंको ध्यान हटाउँछ', 'बिस्तारै चलिरहेको ट्राफिकमा ठीक छ', 'फोन तल समातेर राखेमा ठीक छ', 'सीधा सडकमा ठीक छ'], 'A', 'Traffic Rules', 'Easy',
                'हातमा समातेको फोनले तपाईंका आँखा, हात र ध्यान गाडी चलाउनेबाट हटाउँछ र यो अनुमति छैन।'],
            ['कारमा कसले सिटबेल्ट लगाउनुपर्छ?',
                ['चालक र अगाडिको सिटका यात्रुहरूले', 'चालकले मात्र', 'यात्रुहरूले मात्र', 'कसैले पनि, यो ऐच्छिक छ'], 'A', 'Traffic Rules', 'Easy',
                'हरेक यात्रामा चालक र अगाडिको सिटका यात्रुहरूका लागि सिटबेल्ट अनिवार्य छ।'],
            // Road Safety
            ['मोटरसाइकल चालकले हेलमेट किन लगाउनुपर्छ?',
                ['यसले दुर्घटनामा टाउकोलाई जोगाउँछ, जुन सबैभन्दा गम्भीर चोटको जोखिम हो', 'यसले हावा आँखामा पस्नबाट मात्र रोक्छ', 'राजमार्गमा मात्र चाहिन्छ', 'राति मात्र चाहिन्छ'], 'A', 'Road Safety', 'Easy',
                'दुर्घटनामा चालकका लागि हेलमेट सबैभन्दा प्रभावकारी सुरक्षा हो।'],
            ['सुरक्षित पछ्याउने दूरी के हो?',
                ['सुरक्षित रूपमा रोकिन पर्याप्त खाली ठाउँ, वर्षा वा कम ग्रिपमा अझ ठूलो', 'जुनसुकै गतिमा एक गाडीको लम्बाइ', 'ट्राफिकमा आधा गाडीको लम्बाइ', 'छिटो ब्रेक लगाउन सकेमा खाली ठाउँ चाहिँदैन'], 'A', 'Road Safety', 'Medium',
                'गति जति बढी हुन्छ र ग्रिप जति कम हुन्छ, रोकिने दूरी त्यति लामो हुन्छ।'],
            ['वर्षामा किन गति घटाउनुपर्छ?',
                ['भिजेको सडकले ग्रिप घटाउँछ र रोकिने दूरी लम्ब्याउँछ', 'वर्षाले इन्जिन बिगार्छ', 'वाइपरले धेरै इन्धन खर्च गर्छ', 'अन्य चालकहरूले जे भए पनि गति घटाउँछन्'], 'A', 'Road Safety', 'Easy',
                'टायर र सडकबीच पानी पस्दा ग्रिप घट्छ, त्यसैले ब्रेक लगाउन लामो समय लाग्छ र चिप्लिने सम्भावना बढ्छ।'],
            ['ब्रेक फेल भएमा सबैभन्दा पहिले के गर्नुपर्छ?',
                ['ब्रेक पेडल पम्प गर्नुहोस्, तल्लो गियरमा जानुहोस् र रोकिन सुरक्षित ठाउँतिर स्टेरिङ गर्नुहोस्', 'गतिमै इन्जिन बन्द गर्नुहोस्', 'ह्यान्डब्रेक एकैचोटि कडा तान्नुहोस्', 'कार्यशालासम्म पुग्न गति बढाइरहनुहोस्'], 'A', 'Road Safety', 'Hard',
                'पम्प गर्दा प्रेसर फर्कन सक्छ, इन्जिन ब्रेकिङले गति घटाउँछ, र ह्यान्डब्रेक कडा तान्दा पाङ्ग्रा लक हुन सक्छन्।'],
            ['गाडी चलाउँदा टायर पड्किएमा के गर्नुपर्छ?',
                ['स्टेरिङ बलियोसँग समात्नुहोस्, एक्सिलेटरबाट खुट्टा हटाउनुहोस् र बिस्तारै गति घटाउनुहोस्', 'तुरुन्तै कडा ब्रेक लगाउनुहोस्', 'सडक छेउतिर तीव्र रूपमा स्टेरिङ गर्नुहोस्', 'नियन्त्रण कायम राख्न गति बढाउनुहोस्'], 'A', 'Road Safety', 'Hard',
                'टायर पड्किएपछि अचानक ब्रेक लगाउँदा वा स्टेरिङ गर्दा सवारी नियन्त्रणबाहिर जान सक्छ।'],
            ['लामो यात्रा अघि के जाँच गर्नुपर्छ?',
                ['टायर, ब्रेक, बत्ती, तरल पदार्थको स्तर र इन्धन', 'इन्धन गेज मात्र', 'ऐना मात्र', 'गाडी सुरु भयो भने केही पनि होइन'], 'A', 'Road Safety', 'Easy',
                'यात्राअघिको छोटो जाँचले धेरैजसो बिग्रने र दुर्घटना गराउने खराबी पत्ता लगाउँछ।'],
            ['गाडी चलाउँदा निद्रा लागेमा के गर्नुपर्छ?',
                ['सुरक्षित ठाउँमा रोकिनुहोस् र अघि बढ्नुअघि आराम गर्नुहोस्', 'झ्याल खोलेर गाडी चलाइरहनुहोस्', 'चाँडो पुग्न छिटो चलाउनुहोस्', 'चिया पिएर गतिमै चलाइरहनुहोस्'], 'A', 'Road Safety', 'Easy',
                'निद्राले रक्सीजस्तै प्रतिक्रिया ढिलो बनाउँछ; ताजा हावाले आरामको ठाउँ लिन सक्दैन।'],
            ['राति आउँदै गरेको गाडीको हेडलाइटले आँखा तिरमिराएमा के गर्नुपर्छ?',
                ['सडकको बायाँ किनारातिर हेर्नुहोस् र गति घटाउनुहोस्', 'बत्तीतिर हेरिरहनुहोस्', 'आफ्नो फुल बिम बाल्नुहोस्', 'केही क्षण आँखा चिम्लनुहोस्'], 'A', 'Road Safety', 'Medium',
                'सडकको किनारातिर हेर्दा आँखा तिरमिराएपछि पनि तपाईं लेनमै रहनुहुन्छ।'],
            // Vehicle Knowledge
            ['रातो आयल-प्रेसर चेतावनी बत्तीको अर्थ के हो?',
                ['सकेसम्म चाँडो सुरक्षित रूपमा रोकिनुहोस् र आयल जाँच गर्नुहोस्; गाडी चलाइरहनुहोस्', 'अर्को पेट्रोल पम्पमा पुग्दा आयल थप्नुहोस्, जुनसुकै बेला', 'अर्को महिना आयल बदल्नुपर्छ', 'यो चेतावनी डिजेल सवारीका लागि मात्र हो'], 'A', 'Vehicle Knowledge', 'Medium',
                'आयलको प्रेसर हराउँदा केही मिनेटमै इन्जिन जाम हुन सक्छ, त्यसैले तुरुन्तै सुरक्षित रूपमा रोकिनुहोस्।'],
            ['इन्जिन तापक्रम चेतावनी बत्तीको अर्थ के हो?',
                ['इन्जिन धेरै तातो भएको छ; सुरक्षित रूपमा रोकिनुहोस् र चिसिन दिनुहोस्', 'हिटर अन छ', 'अर्को हप्ता कुल्यान्ट थप्नुपर्छ', 'फ्यान बेल्ट खुकुलो भएको मात्र हो'], 'A', 'Vehicle Knowledge', 'Medium',
                'इन्जिन तातो अवस्थामा चलाइरहँदा गम्भीर र महँगो क्षति हुन्छ।'],
            ['सही टायर प्रेसर किन महत्त्वपूर्ण छ?',
                ['कम हावा भएका टायरले ह्यान्डलिङ बिगार्छ, छिटो बिग्रन्छ र बढी इन्धन खर्च गर्छ', 'यसले आराममा मात्र असर गर्छ', 'यो लामो यात्रामा मात्र महत्त्वपूर्ण छ', 'प्रेसरको सुरक्षामा कुनै असर हुँदैन'], 'A', 'Vehicle Knowledge', 'Medium',
                'गलत प्रेसरले ग्रिप र ब्रेकिङ बदल्छ र टायर पड्किन सक्छ।'],
            ['इन्जिन ब्रेकिङ भनेको के हो?',
                ['सवारीको गति घटाउन तल्लो गियर प्रयोग गर्नु, विशेष गरी ओरालोमा उपयोगी', 'इन्जिन बन्द गरेर ब्रेक लगाउनु', 'गतिमै ह्यान्डब्रेक प्रयोग गर्नु', 'अगाडिका ब्रेकले मात्र ब्रेक लगाउनु'], 'A', 'Vehicle Knowledge', 'Hard',
                'तल्लो गियरले ओरालोमा ब्रेक धेरै तातिन नदिई गति नियन्त्रण गर्छ।'],
            ['लेन बदल्नुअघि ऐना र ब्लाइन्ड स्पट किन जाँच गर्नुपर्छ?',
                ['छेउमा रहेको सवारी ऐनाबाट मात्र नदेखिन सक्छ', 'यो अन्य चालकहरूप्रति शिष्टाचार मात्र हो', 'ऐना कानुनी रूपमा ऐच्छिक छन्', 'ब्लाइन्ड स्पट ठूला सवारीमा मात्र हुन्छ'], 'A', 'Vehicle Knowledge', 'Easy',
                'हरेक सवारीको छेउमा ऐनाले नदेखाउने ब्लाइन्ड स्पट हुन्छ।'],
            ['ह्यान्डब्रेक (पार्किङ ब्रेक) केका लागि हो?',
                ['सवारीलाई स्थिर राख्न, विशेष गरी ढलानमा', 'गतिमै छिटो रोकिन', 'खुट्टाको ब्रेकको ठाउँमा', 'स्टेरिङ लक गर्न'], 'A', 'Vehicle Knowledge', 'Easy',
                'ह्यान्डब्रेकले पार्क गरेको सवारीलाई अड्याउँछ; गतिमै खुट्टाको ब्रेकको ठाउँमा यो प्रयोग हुँदैन।'],
        ];
    }

    /** @return array<int, array{string, string}> */
    private function tutorials(): array
    {
        return [
            ['How the Nepal driving licence process works',
                'Apply through your province transport portal, complete biometrics and document checks, pass the written exam, then pass the trial. Fees and pass marks change, so check the current DoTM notice and your province portal before you apply.'],
            ['Reading road signs: shapes and colours',
                'Learn the system, not just single signs: red-bordered triangles warn, red-bordered circles prohibit, blue circles give mandatory instructions, and rectangular signs inform. If you know the shape and colour, you can read a sign you have never seen before.'],
            ['Overtaking safely on Nepal’s roads',
                'Overtake only on the right, only where you can see far ahead, and never on a bend, a blind hilltop, a bridge or a crossing. Signal, check mirrors and the blind spot, pass briskly without speeding, and return to the left once you are safely clear.'],
            ['Driving in rain and on mountain roads',
                'Slow down before bends, not during them, and use a lower gear on descents so your brakes do not overheat. In rain, double your following gap, brake gently, and never drive through fast-flowing water across the road.'],
            ['Night driving',
                'Dip your headlights for oncoming traffic and when following closely, and drive only as fast as your headlights let you see and stop. If dazzled, look to the left edge of the road and slow down instead of staring at the lights.'],
            ['What to do in a breakdown or brake failure',
                'For a breakdown, get off the carriageway if you can, switch on hazard lights, and warn traffic from a safe distance. For brake failure, pump the pedal, change down, and steer to a safe place to stop; use the handbrake gently, never yank it at speed.'],
            ['Motorcycle safety: helmet, balance and visibility',
                'Wear a helmet on every ride, keep both hands on the bars, and ride where drivers can see you rather than in their blind spots. Wet roads, painted lines and gravel cut grip sharply, so brake earlier and more gently than in the dry.'],
            ['Preparing for the trial test',
                'Practise the marked stations until they are routine: figure-8, narrow plank, U-turn, traffic lights, bumps, ramp and behaviour checks. The trial is scored out of 100 with 70 needed to pass, and a foot down on the plank can fail you outright.'],
            ['Sharing the road with pedestrians and cyclists',
                'Give pedestrians priority at crossings and extra care near schools, bus stops and markets. Pass cyclists with a wide gap, check for them before opening a door or turning across a cycle lane, and never overtake into oncoming cyclists.'],
        ];
    }

    /** @return array<int, array{string, string}> */
    private function examInformation(): array
    {
        return [
            ['Licence categories A and B',
                'Category A is for motorcycles and scooters (minimum age 16) and Category B is for cars, jeeps and vans (minimum age 18). Heavier vehicles use further categories; confirm the exact category lettering with your transport office before applying.'],
            ['The written exam (Likhit)',
                'A computer-based multiple-choice exam in Nepali or English, drawn from the DoTM question bank and covering signs, rules, safety and vehicle basics. Question counts and pass marks have changed over time, so check the current DoTM notice before exam day.'],
            ['The trial (practical) test',
                'A marked course scored out of 100, with 70 needed to pass, covering stations such as the figure-8, narrow plank, U-turn, traffic lights, bumps and ramp. You get three attempts on one written pass; a foot down on the plank can fail you at once.'],
            ['Documents and steps to apply',
                'Apply through your province transport portal, then attend in person for biometrics and document checks. Bring your citizenship certificate and a passport photo, and be ready for blood-group and eye checks. Use your name exactly as on your citizenship.'],
        ];
    }
}
