<?php
/**
 * Site content. Everything here is editable in Appearance → Customize → RamSetu.
 *
 * Text comes from RamSetu's "RamSetu_Website" content document (Sept 2026).
 * Items marked DRAFT below were not in that document and are placeholders for
 * RamSetu to approve or replace. Blank tenure / interest values stay hidden.
 *
 * Multi-paragraph fields: separate paragraphs with a blank line.
 * List fields: one item per line. "Title | text" lines give a heading + description.
 */

defined( 'ABSPATH' ) || exit;

function rs_defaults() {
	static $d = null;
	if ( null !== $d ) {
		return $d;
	}
	$d = array(
		/* Contact & company */
		'phone'            => '',
		'email'            => '',
		'whatsapp'         => '',
		'hq_address'       => "2501, Plan S – Business Park,\nNavi Mumbai – 400706",
		'cin'              => 'U66190MR2026PTC477695',
		'gst'              => '27AAPCR9978L1Z2',
		'grievance_name'   => 'Aaditya Somani',
		'grievance_phone'  => '9828582261',
		'grievance_email'  => 'aaditya.s@ramsetufin.com',
		'grievance_note'   => 'We are committed to addressing customer concerns fairly and transparently. If you have a grievance or complaint, you can contact our designated grievance redressal representative.',
		'social_linkedin'  => '',
		'social_facebook'  => '',
		'social_instagram' => '',
		'social_youtube'   => '',

		/* Shown under product cards and on About Us */
		'loans_disclaimer' => 'RamSetu connects eligible customers with lending partners. All loans are subject to the eligibility, assessment and approval of the respective bank or NBFC.',

		/* Home – slider */
		/* Home – product slider (1.11.0): full-width banners from RamSetu; empty heading hides a slide. */
		'hs1_tab'   => "Home Loans",
		'hs1_brand' => "RamAffordable",
		'hs1_title' => "Supporting Every Step\nTowards Your Dream Home",
		'hs1_text'  => "Affordable Home Loans to make Homeownership a Reality",
		'hs1_chips' => "Loan up to ₹50 Lakhs\nTenure up to 15 Years\nDoorstep Services\nCompetitive Interest Rates",
		'hs1_link'  => "ramaffordable#home-loan",
		'hs1_apply' => "home_loan",
		'hs1_image' => "",
		'hs2_tab'   => "Loan Against Property",
		'hs2_brand' => "RamAffordable",
		'hs2_title' => "Unlock the Potential\nof Your Property",
		'hs2_text'  => "Affordable Loans Against Property for Underserved and New-to-Credit customers to meet Diverse Financial & Business Needs",
		'hs2_chips' => "Loan up to ₹50 Lakhs\nTenure up to 15 Years\nDoorstep Services\nCompetitive Interest Rates",
		'hs2_link'  => "ramaffordable#lap",
		'hs2_apply' => "lap",
		'hs2_image' => "",
		'hs3_tab'   => "Business Loans",
		'hs3_brand' => "RamAffordable",
		'hs3_title' => "Empowering\nEmerging Businesses",
		'hs3_text'  => "Secured Business Loans for MSMEs & Businesses in Small & Emerging Markets",
		'hs3_chips' => "Loan up to ₹50 Lakhs\nTenure up to 15 Years\nDoorstep Services\nCompetitive Interest Rates",
		'hs3_link'  => "ramaffordable#loans",
		'hs3_apply' => "lap",
		'hs3_image' => "",
		'hs4_tab'   => "Solar & Equipment",
		'hs4_brand' => "SetuEmerging",
		'hs4_title' => "Solar and\nEquipment Funding",
		'hs4_text'  => "Structured Financing Solutions for Solar Projects and Business Equipment",
		'hs4_chips' => "Loan up to ₹50 Crores\nTenure up to 15 Years\nDoorstep Services\nMinimal Documentation & Competitive Interest Rates",
		'hs4_link'  => "setuemerging#plant-machinery",
		'hs4_apply' => "plant_machinery",
		'hs4_image' => "",
		'hs5_tab'   => "Plant & Machinery",
		'hs5_brand' => "SetuEmerging",
		'hs5_title' => "Plant and\nMachinery Funding",
		'hs5_text'  => "Tailored Financing Solutions for Plant & Machinery",
		'hs5_chips' => "Loan up to ₹50 Crores\nTenure up to 15 Years\nDoorstep Services\nMinimal Documentation & Competitive Interest Rates",
		'hs5_link'  => "setuemerging#plant-machinery",
		'hs5_apply' => "plant_machinery",
		'hs5_image' => "",
		'hs6_tab'   => "Education & Hospitality",
		'hs6_brand' => "SetuEmerging",
		'hs6_title' => "Finance for Education\n& Hospitality Industry",
		'hs6_text'  => "Right Financing Solutions for Schools, Colleges, Hotels and Restaurants to support Business Growth and Expansion",
		'hs6_chips' => "Loan up to ₹50 Crores\nTenure up to 15 Years\nDoorstep Services\nMinimal Documentation & Competitive Interest Rates",
		'hs6_link'  => "setuemerging#schools",
		'hs6_apply' => "",
		'hs6_image' => "",
		'slide1_tab'     => 'Business Loans',
		'slide1_eyebrow' => 'Bridging Finance, Building Businesses',
		'slide1_title'   => "Turn Business Aspirations Into Growth",
		'slide1_text'    => 'Whether you are expanding your business, strengthening working capital or planning your next milestone, RamSetu connects you with suitable financial solutions from its lending partners.',
		'slide1_cta'     => 'Explore Business Loans',
		'slide1_link'    => "setuemerging",
		'slide1_image'   => '',

		'slide2_tab'     => 'Affordable Loans',
		'slide2_eyebrow' => 'Making Dreams More Achievable',
		'slide2_title'   => 'Affordable Financing for Your Home & Business',
		'slide2_text'    => 'From owning your dream home to supporting your business ambitions, RamAffordable connects you with suitable loan solutions designed around your financial needs.',
		'slide2_cta'     => 'Explore Our Affordable Loans',
		'slide2_link'    => 'ramaffordable',
		'slide2_image'   => '',

		'slide3_tab'     => 'Advisory',
		'slide3_eyebrow' => 'Solutions Built Around Your Business',
		'slide3_title'   => 'Structured Finance for Growing Enterprises',
		'slide3_text'    => 'Explore tailored financial solutions, corporate lending and advisory support designed to address the evolving needs of businesses and help unlock their next stage of growth.',
		'slide3_cta'     => 'Explore Our Advisory Services',
		'slide3_link'    => 'setuemerging',
		'slide3_image'   => '',

		/* Home – about + leadership */
		'home_about_title'     => 'Bridging the Gap Between You and the Right Financial Solutions',
		'home_about_text'      => "RamSetu Financial Services is a solution providing institution connecting individuals and businesses with suitable loan and financial solutions from leading banks and NBFCs. Backed by professionals with 32+ years of experience in banking, NBFCs and lending, RamSetu is focused on making formal finance more accessible across India, particularly in smaller towns and emerging markets.\n\nWe serve Underserved, Underpenetrated, and New-to-Credit customers through a range of affordable lending solutions, including **Home Loans and Loans Against Property**, expanding access to formal credit.\n\nFor the prime segment, we offer **tailored financing solutions** to support business growth and expansion, including **Plant & Machinery Loans, Lease Rental Discounting and Warehouse Discounting, Solar & Equipment Financing, and specialised lending solutions for the Education and Hospitality industries**.\n\nOur focus is to bridge the gap between customers and formal financial institutions, making the process of accessing credit simpler, more transparent and accessible.",
		'home_founder_message' => "With over 32 years of experience across leading financial institutions including IDBI Bank, Citi Bank, ICICI Bank, DHFL, YES Bank, RBL Bank, AXIS Finance and IREP Credit Capital (Viksit Capital), I have had the opportunity to build and scale businesses across Affordable Housing, Home Loans, LAP, Micro-LAP and Wholesale Lending.\n\nOver the years, I have focused on building businesses from the ground up. As Deputy CEO, I worked extensively across Tier 4 and Tier 5 markets, serving nearly 14,000 customers through 93 branches across Andhra Pradesh, Telangana and Karnataka, with an AUM of approximately ₹700 Cr. I also led the launch of 25 branches in Karnataka from ground zero, building teams, processes, governance and technology capabilities while maintaining strong asset quality.\n\nBefore this, as Business Head at Axis Finance, I built the Emerging Market Wholesale business from scratch, taking it across 42 locations and building a ₹3,000+ Cr portfolio, focused on secured lending to businesses across emerging sectors.\n\nRamSetu Financial Services is the culmination of this journey and our vision to bridge the gap between financial institutions and underserved, emerging and growth-oriented customers across India.\n\nThrough our two verticals—RamAffordable and SetuEmerging—we aim to address diverse customer needs, from affordable secured lending to customised and structured financing solutions.\n\nWe aspire to build RamSetu as a trusted, transparent and solution-oriented financial services institution, creating long-term value for our customers, partners and stakeholders.",
		'home_founder_short'   => "With over **32 years of experience** across leading financial institutions, including **IDBI Bank, Citi, ICICI Bank, YES Bank, RBL Bank, AXIS Finance and IREP Credit Capital (Viksit Capital)**, I have built and scaled businesses across Affordable Housing, Home Loans, LAP, Micro-LAP and Wholesale Lending. My experience includes expanding operations across Tier 4 and Tier 5 markets, serving nearly 14,000 customers through 93 branches, and building a ₹3,000+ Cr wholesale lending portfolio.\n\n**RamSetu Financial Services** brings this experience together with a vision to bridge the gap between financial institutions and underserved, emerging and growth-oriented customers across India. Through **RamAffordable** and **SetuEmerging**, we offer affordable secured lending and customised financing solutions, with a commitment to trust, transparency and sustainable, long-term value creation.",

		/* Home – products */
		'products_title' => 'Explore Our Verticals',
		'products_text'  => 'Find the loan that works the best for you.',
		'ra_home_card'   => 'Secured Financial Solutions for Underserved, Underpenetrated and New-to-Credit Customers',
		'ra_vertical_sub' => 'Affordable Home Loans and Loans Against Property',
		'ra_home_list'   => "Affordable Home Loans
Secured Loans Against Property
Balance Transfer and Top Up",
		'se_home_card'   => 'Strategic Financing and Advisory Solutions for Emerging and Growth-Oriented Customers',
		'se_home_list'   => "Plant and Machinery Loans
Loans for Solar Funding
Loans for Equipment Funding
Loans for Schools and Colleges
Loans for Hotels and Restaurants
Lease Rental Discounting and Warehouse Discounting
Balance Transfer and Top Up",

		/* Home – Why RamSetu (approved in Round 1 review) */
		'why_title' => 'Why RamSetu',
		'why_items' => "32+ Years of Banking Experience | Built by banking professionals who understand what both customers and lenders look for.\nA Network of Banks & NBFCs | We connect you with suitable options from our lending partners, not a single product.\nTransparent & Customer-First | Clear guidance at every step, from your first enquiry to the right lending opportunity.",

		/* Home – RamSetu Special Features (from the content document) */
		'special_title' => 'RamSetu Special Features',
		'special_items' => "Loans for Underserved & New-to-Credit Customers |\nLoans for Diverse Customer Profiles | Salaried, MSMEs, Self-Employed, Private Limited Companies, LLPs, Trusts, Societies & more.\nLoans Against Multiple Property Types | Residential, Commercial, Industrial, Mixed-used, Vacant & Rented Properties, Demarcated Plots, Schools, Colleges, Hotels & Restaurants - in individual or entity names.\nPlant, Machinery, Solar & Equipment Finance |\nBalance Transfer + Top-Up at Competitive Rates |\nLoans Upto ₹50 Crores for 15 Years Tenure | With Competitive Rates and Door Step Service",

		/* Shared by RamAffordable and SetuEmerging: "Loans Against Collateral of" */
		'collateral_title' => 'Loans Against Collateral of',
		'collateral_items' => "Residential Properties\nCommercial Properties\nIndustrial Properties\nVacant Plots\nPlants & Machineries\nSolar Equipments\nSchool, College & Hospital Properties",

		/* RamAffordable */
		'ra_tagline'         => 'Affordable Home & Secured Business Loans',
		'ra_brief_title'     => 'Financial Solutions That Fit Your Business & Personal Needs',
		'ra_brief'           => "At RamSetu, we understand that every financial need is different. Whether you are Salaried, a Self Employed Professional, a Self Employed Non-Professional, an MSME or a Business, RamAffordable connects you with suitable loan solutions for your personal and business requirements.\n\nOur offerings include Affordable Home Loans & Loans Against Property (LAP), solutions for underserved & new-to-credit customers with loan options of up to ₹50 lakhs, subject to the eligibility and assessment of the respective lending partner.\n\nWith RamAffordable, we aim to make access to suitable financial solutions simpler, transparent and customer-focused.",
		'ra_features'        => "Loans up to ₹50 Lakhs\nLoans up to 15 Years of Tenure\nBalance Transfer & Top Up with Higher Loan Amount and Lower Interest Rates\nCompetitive Rates and Fees with Door-Step Services",
		'ra_loans_for'       => "Underserved and New-to-Credit Customers\nSalaried\nSelf Employed Professionals\nSelf Employed Non-Professionals\nManufacturers, Traders & Suppliers in Small Towns\nDairy & Arecanut Traders",
		'ra_business_title'  => "Affordable Loans Against Property",
		'ra_business_text'   => "",
		'ra_business_size'   => "",
		'ra_business_tenure' => '',
		'ra_business_rate'   => '',
		'ra_home_title'      => 'Affordable Home Loans',
		'ra_home_text'       => "",
		'ra_home_size'       => "",
		'ra_home_tenure'     => '',
		'ra_home_rate'       => '',
		'ra_photo_captions'  => "MSME & Business Financing\nSalaried & Self-Employed Customers\nHome Loans & Property Financing\nNew-to-Credit Customers",

		/* SetuEmerging */
		'se_tagline'     => 'Tailored Financial Solutions for Your Business',
		'se_brief_title' => 'Tailored Financial Solutions for Growing Businesses',
		'se_brief'       => "At RamSetu, we understand that growing businesses often require financial solutions that go beyond standard lending. SetuEmerging works with businesses and institutions to connect them with structured financing and advisory solutions aligned with their financial requirements and growth objectives.\n\nWith SetuEmerging, our aim is to bridge complex financial requirements with suitable lending and advisory solutions through a structured and customer-focused approach.",
		'se_features'    => "Loans up to ₹50 Crores\nLoans up to 15 Years\nBalance Transfer & Top Up with Higher Loan Amount and Lower Interest Rates\nCompetitive Rates and Fees with Door-Step Services",
		'se_loans_for'   => "Salaried Individuals\nProprietorships, Partnerships, Private Limited Companies\nSolar, Plant & Machinery and Equipment Funding\nHospitality, Education and Tourism Industry\nLease Rental Discounting (LRD) / Warehousing Discounting",
		'se_industries_title' => 'Specially Curated Loans for Businesses in These Industries',
		'se_industries'  => '',
		'se_solutions'   => "Solar, Plant & Machinery and Equipment\nSchools and Colleges\nHotels and Restaurants\nLease Rental / Warehousing Discounting\nOther",
		'se_card_title'  => 'Customized Financial Solutions',
		'se_card_text'   => "",
		'se_size'        => "",
		'se_tenure'      => '',
		'se_rate'        => '',
		'se_photo_captions' => "Hospitality, Education & Tourism\nIndustrial, Commercial & Warehousing Properties\nStructured Finance & Advisory",

		/* About */
		'about_hero'     => 'A trusted bridge to the right financial solutions',
		'about_intro'    => "RamSetu Financial Services is a financial services company built by experienced banking professionals with over 32 years of industry experience. We aim to bridge the gap between individuals and businesses seeking financial solutions and banks and NBFCs offering suitable lending opportunities.\n\nWe serve MSMEs, entrepreneurs, self-employed professionals and salaried customers, including those who may be underserved or new to formal credit.\n\nThrough RamAffordable and SetuEmerging, we connect customers with solutions across home loans, loans against property, business financing, structured finance and advisory services.\n\nInspired by the vision of Lord Ram, our promise is simple: to understand your needs, guide you towards suitable financial solutions and make your lending journey simpler, clearer and more transparent.",
		'about_vision'   => "To become India’s preferred financial solutions partner - powered by the right talent, strong governance, technology and transparent service - bridging the informal and formal financial ecosystem and making trusted, comprehensive financial solutions accessible to customers across the country.",
		'about_mission'  => "To expand access to comprehensive lending solutions across India, with a strong focus on emerging and smaller towns, by bringing diverse financial needs under one roof and making finance simpler, more accessible and transparent for individuals, entrepreneurs and businesses.",
		'about_presence' => "We are currently operating from our regional offices in Tumkur, Karnataka and Vashi, Navi Mumbai, with live markets across Tumkur, Sira and Vashi. We have begun expanding our presence into new markets, with Tiptur, Kunigal, Hindupur, Chikkaballapur, Davangere and Doddaballapura in Karnataka & Pune in Maharashtra identified as upcoming locations. This expansion will enable us to progressively deepen our reach across emerging markets.",
		'about_live'     => 'Tumakuru, Sira, Vashi (Navi Mumbai)',
		'about_upcoming' => "Tiptur, Kunigal, Hindupur, Chikkaballapur, Davangere, Doddaballapura, Pune",
		'roadmap_intro'  => 'RamSetu is building a phased presence across emerging markets, with a focus on bringing accessible financial solutions closer to businesses and customers.',
		'roadmap'        => "Phase 1 — Tumkur Cluster | Establish 5–7 branches by December 2026, driven by the P2 Strategy to achieve optimal productivity and profitability.\nPhase 2 — Andhra Pradesh | Expand into Rajahmundry and surrounding markets including Samarlakota, Bhimavaram, Bobbili, Tadepalligudem and Amalapuram.\nPhase 3 — Telangana | Expand into Karimnagar and nearby markets including Mancherial, Parkal, Warangal, Jangaon and Jagtial.\nPhase 4 — Noida & Uttar Pradesh | Build presence across Noida, Hapur, Meerut, Moradabad, Mathura and Vrindavan.",

		/* Become a Partner (intro and steps are DRAFT; disclaimer is from the brief) */
		'partner_title'      => 'Register as a CA / DSA and earn on every referral',
		'partner_text'       => 'Know a business owner, professional or family looking for a home loan, loan against property or financial advice? Refer them to RamSetu. Our team takes it from there, and you earn for every successful referral.',
		'partner_steps'      => "Register below with your details and PAN\nRamSetu verifies your details and approves your registration\nYou get your Partner ID and login by email, refer customers and earn on every successful loan",
		'partner_disclaimer' => 'All verification will be done by RamSetu without the involvement of the Referring Partner.',

		/* Branch locator */
		'doorstep_title' => "Don't worry — if you can't come to our branch, we will come to your doorstep.",
		'doorstep_text'  => 'Request a call back and we will reach out.',
	);
	return $d;
}

function rs_mod( $key ) {
	$d = rs_defaults();
	return get_theme_mod( 'rs_' . $key, $d[ $key ] ?? '' );
}

/** Split a textarea mod into non-empty lines. */
function rs_lines( $key ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) rs_mod( $key ) ) ) ) );
}

/** Split a textarea mod into paragraphs (blank-line separated). */
function rs_paras( $key ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\n\s*\n/', str_replace( "\r", '', (string) rs_mod( $key ) ) ) ) ) );
}

/** "Title | text" lines → array of array( title, text ). */
function rs_pairs( $key ) {
	$out = array();
	foreach ( rs_lines( $key ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		$out[] = array( $parts[0], $parts[1] ?? '' );
	}
	return $out;
}

function rs_list( $key ) {
	return array_values( array_filter( array_map( 'trim', explode( ',', (string) rs_mod( $key ) ) ) ) );
}

/** Escape text and turn **words** into bold. */
function rs_bold( $text ) {
	return preg_replace( '/\*\*(.+?)\*\*/u', '<strong>$1</strong>', esc_html( $text ) );
}

/** Echo paragraphs for a multi-paragraph field (**words** show in bold). */
function rs_the_paras( $key, $class = '' ) {
	foreach ( rs_paras( $key ) as $p ) {
		printf( '<p%s>%s</p>', $class ? ' class="' . esc_attr( $class ) . '"' : '', rs_bold( $p ) );
	}
}
