<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\FaqTopic;
use App\Models\HeroSlide;
use App\Models\HomeBlock;
use App\Models\Page;
use Illuminate\Database\Seeder;

/** Homepage + help content. Items with is_placeholder=true are stand-ins (image or claim) the owner must confirm/replace. */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        if (! HeroSlide::exists()) {
            HeroSlide::create(['eyebrow' => 'Exclusively from JB Optician', 'kicker' => "WE'RE PROUD TO INTRODUCE", 'heading' => 'JB Optician a online store', 'image' => 'images/placeholders/hero-eye.svg',
                'alt' => 'Contact lens on a fingertip', 'cta_label' => 'SHOP WITH US', 'cta_url' => '/shop', 'position' => 0, 'is_active' => true]);
            HeroSlide::create(['eyebrow' => 'Exclusively from JB Optician', 'kicker' => "WE'RE PROUD TO INTRODUCE", 'heading' => 'JB Optician a online store', 'image' => 'images/placeholders/hero-sunglasses.svg',
                'alt' => 'Sunglasses Collection', 'cta_label' => 'SHOP WITH US', 'cta_url' => '/shop', 'position' => 1, 'is_active' => true]);
        }

        if (! HomeBlock::exists()) {
            $b = fn (array $a) => HomeBlock::create($a + ['is_active' => true, 'is_placeholder' => false]);
            $b(['section' => 'collage', 'title' => 'John Jacobs × Squid Game 2', 'image' => 'images/placeholders/collage-1.svg', 'alt' => 'John Jacobs Special Edition', 'url' => '/product-category/john-jacobs', 'position' => 0]);
            $b(['section' => 'collage', 'title' => 'Sunglasses for women', 'subtitle' => 'Trendy shades for every look', 'image' => 'images/placeholders/collage-2.svg', 'alt' => 'Sunglasses for women', 'url' => '/product-category/sunglasses', 'position' => 1]);
            $b(['section' => 'collage', 'title' => null, 'image' => 'images/placeholders/collage-3.svg', 'alt' => 'Men Eyewear Collection', 'url' => '/product-category/man', 'position' => 2]);
            $b(['section' => 'collage', 'title' => null, 'image' => 'images/placeholders/collage-4.svg', 'alt' => 'Metal frame glasses', 'url' => '/product-category/spectacle-frames', 'position' => 3]);
            $b(['section' => 'promo', 'title' => 'Build up your spectacle today with JB Optician™', 'subtitle' => 'A new revolution. A new perspective.', 'cta_label' => 'SHOP NOW', 'url' => '/shop', 'image' => 'images/placeholders/promo-shopfront.svg', 'alt' => 'JB Optician Store']);
            $b(['section' => 'trust', 'title' => 'Free Shipping', 'subtitle' => 'On orders over ₹2000', 'icon' => 'truck', 'position' => 0]);
            $b(['section' => 'trust', 'title' => 'Dedicated Support', 'subtitle' => 'Quick response 24/7', 'icon' => 'support', 'position' => 1]);
            $b(['section' => 'trust', 'title' => 'Quality Guarantee', 'subtitle' => 'Certified optical products', 'icon' => 'money', 'position' => 2]);
            for ($i = 1; $i <= 5; $i++) {
                $b(['section' => 'social', 'image' => "images/placeholders/social-{$i}.svg", 'alt' => "JB Optician Gallery {$i}", 'url' => null, 'position' => $i]);
            }
        }

        Page::firstOrCreate(['slug' => 'about-us'], ['title' => 'About JB Optician', 'is_active' => true, 'body' =>
            "Welcome to **JB Optician**, where your vision is our mission. We specialize in high-quality eyewear, advanced lenses, and professional eye care services to meet your needs.\n\nOur store offers a wide variety of stylish frames, premium lenses, and modern contact lenses for all age groups. Our experienced staff and latest technology guarantee precision and comfort in every product.\n\nCustomer satisfaction is our top priority, and we strive to provide the best service in the industry."]);
        
        $policies = [
            'privacy-policy' => ['Privacy Policy', "At **JB Optician**, we value your privacy and are committed to safeguarding your personal data.\n\nWe collect relevant information such as your name, shipping address, contact details, and prescription values solely for fulfilling your orders and ensuring accurate optical fitment. Your information is securely stored and never shared with unauthorized third parties."],
            'terms-and-conditions' => ['Terms and Conditions', "Welcome to the **JB Optician** online store. By browsing our catalog and purchasing our products, you agree to our standard store policies.\n\nAll optical products are thoroughly inspected for quality before dispatch. Prices are displayed in Indian Rupees (INR) and are inclusive of applicable taxes."],
            'shipping-policy' => ['Shipping Policy', "We provide secure and tracked delivery across India for all eyewear and accessories.\n\n- Standard delivery timeline is **3-5 business days**.\n- Express delivery is available for select pin codes.\n- **Free Shipping** applies on all orders exceeding ₹2,000."],
            'returns-and-refunds' => ['Returns & Refunds', "We stand behind the quality of every frame and lens we craft.\n\n- If your delivered product has any manufacturing defect or incorrect power, contact us within **7 days** of delivery.\n- We will arrange a free replacement or full refund after product inspection."]
        ];
        foreach ($policies as $slug => [$title, $body]) {
            Page::firstOrCreate(['slug' => $slug], ['title' => $title, 'is_active' => true, 'body' => $body]);
        }

        if (! FaqTopic::exists()) {
            $topics = [
                'Order Issues' => [
                    ["How can I change my order after it's been placed?", null], ['What should I do if I receive a damaged item?', null], ['Can I cancel my order?', 'Orders that are still awaiting payment can be cancelled from My Account > Orders. For any other order, please contact us.'],
                    ['How do I track my order?', 'Log in and open My Account > Orders to see the current status of each order.'],
                ],
                'Shipping Information' => [['What shipping options are available?', null], ['How long will it take to receive my order?', null], ['Do you ship internationally?', null], ['How can I change my shipping address?', null]],
                'Returns & Refunds' => [['What is your return policy?', null], ['How do I return an item?', null], ['When will I receive my refund?', null], ['Can I exchange an item instead of returning it?', null]],
                'Account Issues' => [['How do I reset my password?', 'Click "Lost your password?" on the login form and we will email you a reset link.'], ['How can I update my account information?', 'Log in and go to My Account > Account details.'],
                    ["What should I do if I can't access my account?", 'Use the "Lost your password?" link, or contact us if the problem continues.'], ['How do I delete my account?', null]],
                'Payment Issues' => [['What payment methods do you accept?', 'Cash on delivery and online payment through Razorpay (cards, UPI, netbanking, wallets).'], ['What should I do if my payment is declined?', null],
                    ['How can I apply a discount code?', 'Enter your code in the "Add a coupon" box on the cart page.']],
                'Technical Support' => [['How can I contact customer service?', 'Use the Contact Us page or the contact buttons at the bottom of this page.'], ['Where are your stores located?', 'Shop 1, Matrutva Maternity & Nursing Hospital, Colony, Ulkanagari Rd, near Omkareshwar Mandir, Garkheda Area, Chhatrapati Sambhajinagar, Maharashtra 431005.']],
            ];
            $pos = 0;
            foreach ($topics as $title => $faqs) {
                $t = FaqTopic::create(['title' => $title, 'position' => $pos++, 'is_active' => true]);
                foreach ($faqs as $i => [$q, $a]) {
                    Faq::create(['faq_topic_id' => $t->id, 'question' => $q, 'answer' => $a, 'position' => $i, 'is_active' => true]);
                }
            }
        }
    }
}
