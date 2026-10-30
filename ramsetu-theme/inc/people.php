<?php
/**
 * Leadership, Advisors, Supporters & Partners and Team profiles from RamSetu's content (Oct 2026).
 * Seeded once into Team & Advisors; edit them there afterwards.
 */
defined( 'ABSPATH' ) || exit;

define( 'RS_FOUNDER_BIO', "Seasoned financial services leader with 32+ years of experience across secured lending, retail finance, emerging markets, and wholesale lending, with leadership experience across institutions including Dabur Finance, IDBI Bank, Citibank, ICICI Bank, DHFL, YES Bank, RBL Bank, IndusInd Bank, AXIS Finance, and IREP Credit Capital. Proven expertise in building businesses from the ground up, scaling operations, developing distribution networks, and driving sustainable growth across diverse and emerging markets.\n\nStrong track record in Affordable Housing, Home Loans, Loan Against Property, Micro-LAP, and Wholesale Lending, with experience in building high-performing teams, expanding geographic presence, strengthening credit and risk frameworks, and delivering business growth at scale. Successfully led businesses across South India and emerging markets, combining deep market understanding with disciplined execution and customer-centricity.\n\nKnown for strong people leadership and institution building—developing teams, nurturing talent, building trusted relationships, and creating an execution-oriented culture. Passionate about financial inclusion and expanding access to responsible finance, particularly for MSMEs, self-employed individuals, and customers across underserved and emerging markets." );

function rs_default_advisors() {
	return array(
		array( 'Aarti Kapoor', "Dynamic, results-driven thought leader with 36 years of experience across financial services, including Bank of America, GE Capital, and Citigroup. Proven expertise in scaling Global operations, driving organizational transformation, and leading diverse teams across India, Philippines, UK, and US. Skilled in complex change management, risk governance, and strategic execution, consistently delivering innovative solutions that boost revenue, optimize costs, and enable sustainable growth.\n\nKnown for strong people leadership—building trust, inspiring followership, and fostering collaborative, high-impact teams that achieve meaningful outcomes. Passionate about social impact through NGO fundraising, CSR initiatives, and a committed advocate for Diversity, Equity & Inclusion (DEI)." ),
		array( 'Mr. Ajay Shah', "38+ years of technology leadership across Enterprise Architecture, Digital Transformation, IT Infrastructure, Cybersecurity, Data & Platforms, and Technology Governance, with a strong track record of shaping technology strategy, driving enterprise-wide transformation, and building scalable technology ecosystems.\n\nExperienced in leading complex technology initiatives, strengthening governance and security frameworks, and enabling organizations through innovation, digital transformation, and strategic technology execution." ),
		array( 'Mr. Bipin Oberoi', "Seasoned financial services leader with extensive experience across Credit, Mortgage Lending, Underwriting, and Portfolio Management, with a strong track record of building robust credit frameworks, shaping lending strategies, managing portfolio quality, and driving sustainable business growth.\n\nKnown for strong credit leadership, sound risk judgement, and disciplined execution, with deep expertise in balancing business growth with prudent risk management across lending businesses." ),
	);
}


/** Advisor designations (About Us, 17 Oct 2026). */
function rs_advisor_designations() {
	return array(
		'Aarti Kapoor' => 'Strategic Advisor – Organizational Excellence & Transformation',
	);
}

/**
 * Our Steadfast Supporters & Partners: [ name, category (shown as the heading), bio, photo file in assets/img/people/ ].
 */
function rs_default_supporters() {
	return array(
		array(
			'Mr. Shivashankar N. Gangappa',
			'Technology & Digital Enablement Partner',
			"An accomplished entrepreneur and strategic leader with extensive experience in building and scaling science and technology businesses across research, diagnostics, and specialised engineering. Founder and Managing Director of Liveon Biolabs Pvt. Ltd. and Liveon Technolabs Pvt. Ltd., he brings expertise in business development, innovation, and operational management.\n\nCombining entrepreneurial vision with a strong focus on execution, he is committed to building sustainable businesses and creating meaningful impact through technology and innovation.\n\nRamSetu extends its sincere gratitude to Mr. Shivashankar N. Gangappa for his steadfast support and invaluable contribution to our journey. He has been a pillar of our foundation in South Karnataka, helping us connect with the right people, establish essential technology capabilities, and identify the right solutions to strengthen our operations and support our growth.",
			'supporter-shivashankar.jpg',
		),
		array(
			'Siddaganga Institute of Technology, Tumkur, Karnataka',
			'Campus & Talent Development Partners',
			'RamSetu is proud to partner with Siddaganga Institute of Technology as a Campus & Talent Development Partner, with a shared commitment to nurturing and developing future-ready professionals. Through this association, we aim to bridge academic learning with practical industry experience, enabling young talent to build essential skills, take on meaningful responsibilities, and develop professionally. Our focus is on fostering continuous learning, strengthening capabilities, and creating an environment where individuals can realise their potential and build successful, long-term careers.',
			'supporter-sit.jpg',
		),
	);
}

/** Meet RamSetu Team: [ name, designation, photo file in assets/img/people/ ] in display order. */
function rs_default_team() {
	return array(
		array( 'Rachana Bajaj', 'HR & Strategy', 'team-rachana-bajaj.jpg' ),
		array( 'Aaditya Somani', 'Product Manager', 'team-aaditya-somani.jpg' ),
		array( 'Thimmaraya H', 'Area Sales Manager', 'team-thimmaraya-h.jpg' ),
		array( 'Shivakumar RM', 'Senior Branch Manager, Sira', 'team-shivakumar-rm.jpg' ),
		array( 'Maruthi', 'Senior Branch Manager, Tumkur', 'team-maruthi.jpg' ),
	);
}
