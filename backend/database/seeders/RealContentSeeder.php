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
