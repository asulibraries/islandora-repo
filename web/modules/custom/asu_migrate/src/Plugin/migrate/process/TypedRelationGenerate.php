<?php

namespace Drupal\asu_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Check if term exists and create new if doesn't.
 *
 * @MigrateProcessPlugin(
 *   id = "typed_relation_generate"
 * )
 */
class TypedRelationGenerate extends NameURIGenerate {
  /**
   * This is the mapping between relators and their names.
   *
   * @var array
   */
  protected $relatorMap = [
    "relators:abr" => "Abridger",
    "relators:act" => "Actor",
    "relators:adp" => "Adapter",
    "relators:rcp" => "Addressee",
    "relators:anl" => "Analyst",
    "relators:anm" => "Animator",
    "relators:ann" => "Annotator",
    "relators:apl" => "Appellant",
    "relators:ape" => "Appellee",
    "relators:app" => "Applicant",
    "relators:arc" => "Architect",
    "relators:arr" => "Arranger",
    "relators:acp" => "Art copyist",
    "relators:adi" => "Art director",
    "relators:art" => "Artist",
    "relators:ard" => "Artistic director",
    "relators:asg" => "Assignee",
    "relators:asn" => "Associated name",
    "relators:att" => "Attributed name",
    "relators:auc" => "Auctioneer",
    "relators:aut" => "Author",
    "relators:aqt" => "Author in quotations or text abstracts",
    "relators:aft" => "Author of afterword, colophon, etc.",
    "relators:aud" => "Author of dialog",
    "relators:aui" => "Author of introduction, etc.",
    "relators:ato" => "Autographer",
    "relators:ant" => "Bibliographic antecedent",
    "relators:bnd" => "Binder",
    "relators:bdd" => "Binding designer",
    "relators:blw" => "Blurb writer",
    "relators:bkd" => "Book designer",
    "relators:bkp" => "Book producer",
    "relators:bjd" => "Bookjacket designer",
    "relators:bpd" => "Bookplate designer",
    "relators:bsl" => "Bookseller",
    "relators:brl" => "Braille embosser",
    "relators:brd" => "Broadcaster",
    "relators:cll" => "Calligrapher",
    "relators:ctg" => "Cartographer",
    "relators:cas" => "Caster",
    "relators:cns" => "Censor",
    "relators:chr" => "Choreographer",
    "relators:clb" => "Collaborator",
    "relators:cng" => "Cinematographer",
    "relators:cli" => "Client",
    "barrettrelators:cau" => "Co-author",
    "relators:cor" => "Collection registrar",
    "relators:col" => "Collector",
    "relators:clt" => "Collotyper",
    "relators:clr" => "Colorist",
    "relators:cmm" => "Commentator",
    "relators:cwt" => "Commentator for written text",
    "relators:com" => "Compiler",
    "relators:cpl" => "Complainant",
    "relators:cpt" => "Complainant-appellant",
    "relators:cpe" => "Complainant-appellee",
    "relators:cmp" => "Composer",
    "relators:cmt" => "Compositor",
    "relators:ccp" => "Conceptor",
    "relators:cnd" => "Conductor",
    "relators:con" => "Conservator",
    "relators:csl" => "Consultant",
    "relators:csp" => "Consultant to a project",
    "relators:cos" => "Contestant",
    "relators:cot" => "Contestant-appellant",
    "relators:coe" => "Contestant-appellee",
    "relators:cts" => "Contestee",
    "relators:ctt" => "Contestee-appellant",
    "relators:cte" => "Contestee-appellee",
    "relators:ctr" => "Contractor",
    "relators:ctb" => "Contributor",
    "relators:cpc" => "Copyright claimant",
    "relators:cph" => "Copyright holder",
    "relators:crr" => "Corrector",
    "relators:crp" => "Correspondent",
    "relators:cst" => "Costume designer",
    "relators:cou" => "Court governed",
    "relators:crt" => "Court reporter",
    "relators:cov" => "Cover designer",
    "relators:cre" => "Creator",
    "relators:cur" => "Curator",
    "relators:dnc" => "Dancer",
    "relators:dtc" => "Data contributor",
    "relators:dtm" => "Data manager",
    "relators:dte" => "Dedicatee",
    "relators:dto" => "Dedicator",
    "relators:dfd" => "Defendant",
    "relators:dft" => "Defendant-appellant",
    "relators:dfe" => "Defendant-appellee",
    "relators:dgc" => "Degree Committee Member",
    "barrettrelators:dgc" => "Committee Member",
    "relators:dgg" => "Degree granting institution",
    "relators:dgs" => "Degree supervisor",
    "relators:dln" => "Delineator",
    "relators:dpc" => "Depicted",
    "relators:dpt" => "Depositor",
    "relators:dsr" => "Designer",
    "relators:drt" => "Director",
    "relators:dis" => "Dissertant",
    "relators:dbp" => "Distribution place",
    "relators:dst" => "Distributor",
    "relators:dnr" => "Donor",
    "relators:drm" => "Draftsman",
    "relators:dub" => "Dubious author",
    "relators:edt" => "Editor",
    "relators:edc" => "Editor of compilation",
    "relators:edm" => "Editor of moving image work",
    "relators:elg" => "Electrician",
    "relators:elt" => "Electrotyper",
    "relators:enj" => "Enacting jurisdiction",
    "relators:eng" => "Engineer",
    "relators:egr" => "Engraver",
    "relators:etr" => "Etcher",
    "relators:evp" => "Event place",
    "relators:exp" => "Expert",
    "relators:fac" => "Facsimilist",
    "relators:fld" => "Field director",
    "relators:fmd" => "Film director",
    "relators:fds" => "Film distributor",
    "relators:flm" => "Film editor",
    "relators:fmp" => "Film producer",
    "relators:fmk" => "Filmmaker",
    "relators:fpy" => "First party",
    "relators:frg" => "Forger",
    "relators:fmo" => "Former owner",
    "relators:fnd" => "Funder",
    "relators:gis" => "Geographic information specialist",
    "relators:grt" => "Graphic technician",
    "relators:hnr" => "Honoree",
    "relators:hst" => "Host",
    "relators:his" => "Host institution",
    "relators:ilu" => "Illuminator",
    "relators:ill" => "Illustrator",
    "relators:ins" => "Inscriber",
    "relators:itr" => "Instrumentalist",
    "relators:ive" => "Interviewee",
    "relators:ivr" => "Interviewer",
    "relators:inv" => "Inventor",
    "relators:isb" => "Issuing body",
    "relators:jud" => "Judge",
    "relators:jug" => "Jurisdiction governed",
    "relators:lbr" => "Laboratory",
    "relators:ldr" => "Laboratory director",
    "relators:lsa" => "Landscape architect",
    "relators:led" => "Lead",
    "relators:len" => "Lender",
    "relators:lil" => "Libelant",
    "relators:lit" => "Libelant-appellant",
    "relators:lie" => "Libelant-appellee",
    "relators:lel" => "Libelee",
    "relators:let" => "Libelee-appellant",
    "relators:lee" => "Libelee-appellee",
    "relators:lbt" => "Librettist",
    "relators:lse" => "Licensee",
    "relators:lso" => "Licensor",
    "relators:lgd" => "Lighting designer",
    "relators:ltg" => "Lithographer",
    "relators:lyr" => "Lyricist",
    "relators:mfp" => "Manufacture place",
    "relators:mfr" => "Manufacturer",
    "relators:mrb" => "Marbler",
    "relators:mrk" => "Markup editor",
    "relators:med" => "Medium",
    "relators:mdc" => "Metadata contact",
    "relators:mte" => "Metal-engraver",
    "relators:mtk" => "Minute taker",
    "relators:mod" => "Moderator",
    "relators:mon" => "Monitor",
    "relators:mcp" => "Music copyist",
    "relators:msd" => "Musical director",
    "relators:mus" => "Musician",
    "relators:nrt" => "Narrator",
    "relators:osp" => "Onscreen presenter",
    "relators:opn" => "Opponent",
    "relators:orm" => "Organizer",
    "relators:org" => "Originator",
    "relators:oth" => "Other",
    "relators:own" => "Owner",
    "relators:pan" => "Panelist",
    "relators:ppm" => "Papermaker",
    "relators:pta" => "Patent applicant",
    "relators:pth" => "Patent holder",
    "relators:pat" => "Patron",
    "relators:prf" => "Performer",
    "relators:pma" => "Permitting agency",
    "relators:pht" => "Photographer",
    "relators:ptf" => "Plaintiff",
    "relators:ptt" => "Plaintiff-appellant",
    "relators:pte" => "Plaintiff-appellee",
    "relators:plt" => "Platemaker",
    "relators:pra" => "Praeses",
    "relators:pre" => "Presenter",
    "relators:prt" => "Printer",
    "relators:pop" => "Printer of plates",
    "relators:prm" => "Printmaker",
    "relators:prc" => "Process contact",
    "relators:pro" => "Producer",
    "relators:prn" => "Production company",
    "relators:prs" => "Production designer",
    "relators:pmn" => "Production manager",
    "relators:prd" => "Production personnel",
    "relators:prp" => "Production place",
    "relators:prg" => "Programmer",
    "relators:pdr" => "Project director",
    "relators:pfr" => "Proofreader",
    "relators:prv" => "Provider",
    "relators:pup" => "Publication place",
    "relators:pbl" => "Publisher",
    "relators:pbd" => "Publishing director",
    "relators:ppt" => "Puppeteer",
    "relators:rdd" => "Radio director",
    "relators:rpc" => "Radio producer",
    "relators:rce" => "Recording engineer",
    "relators:rcd" => "Recordist",
    "relators:red" => "Redaktor",
    "relators:ren" => "Renderer",
    "relators:rpt" => "Reporter",
    "relators:rps" => "Repository",
    "relators:rth" => "Research team head",
    "relators:rtm" => "Research team member",
    "relators:res" => "Researcher",
    "relators:rsp" => "Respondent",
    "relators:rst" => "Respondent-appellant",
    "relators:rse" => "Respondent-appellee",
    "relators:rpy" => "Responsible party",
    "relators:rsg" => "Restager",
    "relators:rsr" => "Restorationist",
    "relators:rev" => "Reviewer",
    "relators:rbr" => "Rubricator",
    "relators:sce" => "Scenarist",
    "relators:sad" => "Scientific advisor",
    "relators:aus" => "Screenwriter",
    "relators:scr" => "Scribe",
    "relators:scl" => "Sculptor",
    "relators:spy" => "Second party",
    "relators:sec" => "Secretary",
    "relators:sll" => "Seller",
    "relators:std" => "Set designer",
    "relators:stg" => "Setting",
    "relators:sgn" => "Signer",
    "relators:sng" => "Singer",
    "relators:sds" => "Sound designer",
    "relators:spk" => "Speaker",
    "relators:spn" => "Sponsor",
    "relators:sgd" => "Stage director",
    "relators:stm" => "Stage manager",
    "relators:stn" => "Standards body",
    "relators:str" => "Stereotyper",
    "relators:stl" => "Storyteller",
    "relators:sht" => "Supporting host",
    "relators:srv" => "Surveyor",
    "relators:tch" => "Teacher",
    "relators:tcd" => "Technical director",
    "relators:tld" => "Television director",
    "relators:tlp" => "Television producer",
    "barrettrelators:ths" => "Thesis director",
    "relators:ths" => "Thesis advisor",
    "relators:trc" => "Transcriber",
    "relators:trl" => "Translator",
    "relators:tyd" => "Type designer",
    "relators:tyg" => "Typographer",
    "relators:uvp" => "University place",
    "relators:vdg" => "Videographer",
    "relators:voc" => "Vocalist",
    "relators:vac" => "Voice actor",
    "relators:wit" => "Witness",
    "relators:wde" => "Wood engraver",
    "relators:wdc" => "Woodcutter",
    "relators:wam" => "Writer of accompanying material",
    "relators:wac" => "Writer of added commentary",
    "relators:wal" => "Writer of added lyrics",
    "relators:wat" => "Writer of added text",
    "relators:win" => "Writer of introduction",
    "relators:wpr" => "Writer of preface",
    "relators:wst" => "Writer of supplementary textual content",
  ];

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_array($value)) {
      $relator = (array_key_exists('rel', $value) ?
        (strstr($value['rel'], 'relators:') ? $value['rel'] : 'relators:' . $value['rel']) : '');
      unset($value['rel']);
    }
    else {
      if (array_key_exists('relator', $this->configuration)) {
        $relator = $this->configuration['relator'];
      }
      else {
        $parts = explode($this->configuration['delimiter'], $value);
        if (count($parts) > 1) {
          // Allows the configuration to specify which order the relator is in.
          if (array_key_exists('relator_position', $this->configuration)) {
            $relator_position = $this->configuration['relator_position'];
            $relator_string = $parts[$relator_position];
            unset($parts[$relator_position]);
          }
          else {
            // Assumes an order of name|uri|relator.
            $relator_string = array_pop($parts);
          }
          $relator = $this->lookUpRelator($relator_string);
          $value = implode($this->configuration['delimiter'], $parts);
        }
        else {
          $relator = 'relators:ctb';
        }
      }
    }
    $term = parent::transform($value, $migrate_executable, $row, $destination_property);
    $typed_relation = [
      'rel_type' => $relator,
      'target_id' => $term,
    ];
    return $typed_relation;
  }

  /**
   * This looks up a relator code based on the name.
   *
   * Note: this can take either a relator value OR a relator key such as
   * "relators:msd" or even "relator:aut".
   *
   * @param string $relator
   *   The relator part of the working string.
   *
   * @return string
   *   The array key that matches the relator that was provided.
   */
  public function lookUpRelator(string $relator) {
    // Allow lookup to function when passing the relators:xyz key instead of
    // a value.
    $relator_found = 'relators:ctb';
    if (strstr($relator, "relators:") || strstr($relator, "relator:")) {
      $key = 'relators:' . str_replace([
        'relators:',
        'relator:',
      ], "", $relator);
      $relator_by_code_found = array_key_exists($key, $this->relatorMap);
      if (!($relator_by_code_found === FALSE)) {
        $relator_found = $key;
      }
    }
    else {
      if ($relator == "Advisor") {
        $relator = "Thesis advisor";
      }
      $relator_found = array_search(strtolower(trim($relator)), array_map('strtolower', $this->relatorMap));
    }
    return $relator_found;
  }

}
