import nltk
import sys
import re
import string
import urllib
import pickle
import hashlib
import os
import json
import unicodedata

dataDir = '/home/dbpedia/transcript/data/'


totalSteps = 22


###################3
# We depend on a number of files, make sure they are there, if not, create them 
if os.path.exists(dataDir + "blacklist.txt") == False:
		f = open(dataDir + "blacklist.txt", 'w')
		f.writelines("\n")
		f.close()
if os.path.exists(dataDir + "notNameParts.txt") == False:
		f = open(dataDir + "notNameParts.txt", 'w')
		f.writelines("\n")
		f.close()
if os.path.exists(dataDir + "globalIgnore.txt") == False:
		f = open(dataDir + "globalIgnore.txt", 'w')
		f.writelines("\n")
		f.close()		
if os.path.exists(dataDir + "regexPatterns.txt") == False:
		f = open(dataDir + "regexPatterns.txt", 'w')
		f.writelines("\n")
		f.close()				
if os.path.exists(dataDir + "globalSameAs.txt") == False:
		f = open(dataDir + "globalSameAs.txt", 'w')
		f.writelines("\n")
		f.close()		
if os.path.exists(dataDir + "globalAuthority.txt") == False:
		f = open(dataDir + "globalAuthority.txt", 'w')
		f.writelines("\n")
		f.close()				
if os.path.exists(dataDir + "globalAuthorityNotes.txt") == False:
		f = open(dataDir + "globalAuthorityNotes.txt", 'w')
		f.writelines("\n")
		f.close()	
if os.path.exists(dataDir + "globalOther.txt") == False:
		f = open(dataDir + "globalOther.txt", 'w')
		f.writelines("\n")
		f.close()	
if os.path.exists(dataDir + "publishedFileNames.txt") == False:
		f = open(dataDir + "publishedFileNames.txt", 'w')
		f.writelines("\n")
		f.close()			
		
#load our black list
f = open(dataDir + "blacklist.txt", 'r')
blacklist = f.read().strip()
blacklist = blacklist.strip().lower()
blacklist = blacklist.split("\n")

#load our not a name part
#these are words that should not appear in a name, meaning, it is not a name
f = open(dataDir + "notNameParts.txt", 'r')
notNameParts = f.read().strip()
notNameParts = notNameParts.strip().lower()
notNameParts = notNameParts.split("\n")

f = open(dataDir + "globalIgnore.txt", 'r')
globalIgnore = f.read().strip()
globalIgnore = globalIgnore.split("\n")
 
f = open(dataDir + "regexPatterns.txt", 'r')
regexPatterns = f.read().strip()
regexPatterns = regexPatterns.split("\n")

f = open(dataDir + "globalOther.txt", 'r')
globalOther = f.read().strip()
globalOther = globalOther.split("\n")


f = open(dataDir + "globalSameAs.txt", 'r')
globalSameAsLines = f.read().strip()
globalSameAsLines = globalSameAsLines.split("\n")
globalSameAs = []
for x in globalSameAsLines:
	if x != '':
		set = x.split(',')
		globalSameAs.append(set)

f = open(dataDir + "globalAuthority.txt", 'r')
globalAuthorityLines = f.read().strip()
globalAuthorityLines = globalAuthorityLines.split("\n") 
globalAuthority = []
for x in globalAuthorityLines:
	if x != '':
		set = x.split(',')
		globalAuthority.append(set)
	

f = open(dataDir + "globalAuthorityNotes.txt", 'r')
globalAuthorityNotesLines = f.read().strip()
globalAuthorityNotesLines = globalAuthorityNotesLines.split("\n") 
globalAuthorityNotes = []
for x in globalAuthorityNotesLines:
	if x != '':
		set = x.split('|')
		globalAuthorityNotes.append(set)
	
	
	
def main():
  
  
	if (len(sys.argv) != 2):
		print "Error: No no filename given"
		sys.exit()
  
	fileName = dataDir + sys.argv[1]
	fileNameOrg = sys.argv[1].replace('.txt','')

	
	authorityNames = {}
	authorityNamesList = []
	authorityCollisons = []
	activeStep = 0
	
	
	activeStep = activeStep + 1
	updateProgress("Loading directory names",fileName,activeStep)
	
	#index is a name, value is its perfered term
	sameAs = {}
 
	#jazzData is a extract created by the dictonary creation process, it contains the names and other stuff in triple form
	f = open(dataDir + "../../data/jazzData.nt", 'r')
	
	for line in f:
	
		quad = line.split()
		#if quad[1] == '<http://www.w3.org/2004/02/skos/core#prefLabel>' or quad[1] == '<http://www.w3.org/2004/02/skos/core#altLabel>' or quad[1] == '<http://xmlns.com/foaf/0.1/name>':
		if  quad[1] == '<http://xmlns.com/foaf/0.1/name>':
			name = ' '.join(quad[2:])
			if name.find('@EN') != -1:
				name = name[0:name.find('@EN')]
			if name.find('@en') != -1:
				name = name[0:name.find('@en')]	
			

			name = name[1:len(name)-1]

			m = re.search("\d", name)
			if m:
				name = name[0:m.start()]

			
			if name.find(',') != -1:
				
				comma = name.split(',')
				newComma = []
				hasJrSr = ''
				for x in comma:
					x=x.strip()
					x=x.replace(".","")	
					
					if len(x)!=0:
					
						if x == 'Jr' or x == 'Sr' or x == 'II' or x == 'III':
							hasJrSr = ' ' + x
						else:
							newComma.append(x)
				
				
				nameFinal = ''.join(comma[1:]) + ' ' + comma[0] + hasJrSr
				nameFinal = nameFinal.replace('  ',' ')
				nameFinal = nameFinal.strip()
				
				name = nameFinal
			
			
			name = name.replace("Jr.","Jr")				
			name = name.replace("Sr.","Sr")	
			name = name.strip()

			
			
			if quad[0].find('/resource/') != -1:
				uriName = formatName(quad[0].split('/resource/')[1])
				uriName = uriName.replace(",","")
				uriName = uriName.replace("Jr.","Jr")				
				uriName = uriName.replace("Sr.","Sr")				
				uriName = uriName.strip()
			
			
			#if len(uriName.split()) >1:
									
			if authorityNames.has_key(uriName):
				if authorityNames[uriName] != quad[0]:
					#print "Collison",authorityNames[uriName], quad[0]						
					if authorityNames[uriName] not in authorityCollisons:
						authorityCollisons.append(authorityNames[uriName])
			
			authorityNames[uriName] = quad[0]
	
			#if len(name.split()) >1:
			
			if authorityNames.has_key(name):
				if authorityNames[name] != quad[0]:
					#print "Collison",authorityNames[name], quad[0]
					if authorityNames[name] not in authorityCollisons:
						authorityCollisons.append(authorityNames[name])
					
			authorityNames[name] = quad[0]
 
		#LOC
		elif quad[1] == '<http://www.w3.org/2004/02/skos/core#prefLabel>' or quad[1] == '<http://www.w3.org/2004/02/skos/core#altLabel>':

			name = ' '.join(quad[2:])
			if name.find('@EN') != -1:
				name = name[0:name.find('@EN')]
			if name.find('@en') != -1:
				name = name[0:name.find('@en')]	
			
			name = name[1:len(name)-1]			
			name = name.strip()
			
			#just included the first and last name. (which may not be last name, but LOC labels way too variable to devote too much time on)
			name = name.split(',')			
			if len(name) > 1:			
				name = name[1] + ' ' + name[0]
				name = name.strip()

				#more useful if the dbpedia url
				if authorityNames.has_key(name) == False:
					authorityNames[name] = quad[0]
 
			
		
		
				
 
 
	
	f.close() 
	
	#for name, uri in authorityNames.iteritems():
	#	print name,uri
	

	
	#load the words corpus	
	wordCorpusFile = nltk.data.load('nltk:corpora/words/en','raw')
	wordCorpusFile = wordCorpusFile.strip()
	wordCorpusFile = wordCorpusFile.split("\n")	

	wordCorpus={}
	for word in wordCorpusFile:
		wordCorpus[word] = word	
	
	 
	
	firstNames={}
	 
	f = open("/home/dbpedia/extracts/firstNames.txt", 'r')
	firstNamesFile = f.read().strip()
	firstNamesFile	= firstNamesFile.replace("\r",'')
	firstNamesFile = firstNamesFile.split("\n")	
	
	
		
	for name in firstNamesFile:
 		firstNames[name] = name	
	  
	assumtions = []
	
	interviewees=[]
	interviewers=[]
	
	foundNames = []
	possiblePartials = []
	fullTextReplace = [] 
	
	#reporting Vars
	namesMatched=[]
	namesNLP=[]

	allSentences = []
		
	#other non name entities

	otherEnts = []

	nlpData = []
	
	
	
	#the user rules passed back from the front end
	userRulesIgnoreLocal = []
	userRulesIgnoreGlobal = []
	userRulesOtherNames = []
	userRulesSameAs = []
	userRulesManualNames = []
	userRulesIntervieweesNames = []
	userRulesIntervieweesSplits = []
	userRulesInterviewersNames = []
	userRulesInterviewersSplits = []
	userRulesSplitRegex = ''
	userRulesIgnoreCountTest = False
	userRulespartialAprovals = []
	userAuthorityControl = []
	userPublish = {}
	userName = 'none'
	
	activeStep = activeStep + 1
	updateProgress("Processing User rules",fileName,activeStep)	
	
	f = open(fileName, 'r')
	entire_file = f.read()
	
	#we use the md5 of the file to keep track of file names and such
	md5 = hashlib.md5()
	md5.update(entire_file)
	fileMd5 = md5.hexdigest()
	

	
	
	#load the user rules if exitsts
	if os.path.exists(dataDir + fileMd5 + '_userRules.json'):
 
		f = open(dataDir + fileMd5 + '_userRules.json', 'r')
		jsonStr = f.read()
		f.close()
		jsonObj = json.loads(jsonStr)
	 
		
		if 'ignoreLocal' in jsonObj:			
			for x in jsonObj['ignoreLocal']:
				userRulesIgnoreLocal.append(x)
				
		if 'ignoreGlobal' in jsonObj:			
			for x in jsonObj['ignoreGlobal']:
				userRulesIgnoreGlobal.append(x)

		if 'otherName' in jsonObj:			
			for x in jsonObj['otherName']:
				userRulesOtherNames.append(x)
				
		if 'sameAs' in jsonObj:			
			for x in jsonObj['sameAs']:
				x['sameAs'] = strip_accents(x['sameAs'])
				x['org'] = strip_accents(x['org'])
				userRulesSameAs.append(x)

		if 'manualNames' in jsonObj:			
			for x in jsonObj['manualNames']:
				userRulesManualNames.append(x)

		if 'intervieweesNames' in jsonObj:			
			for x in jsonObj['intervieweesNames']:
				if x != "":
					userRulesIntervieweesNames.append(x)				
			
		if 'intervieweesSplits' in jsonObj:			
			for x in jsonObj['intervieweesSplits']:
				if x != "":
					userRulesIntervieweesSplits.append(x)	

		if 'interviewersNames' in jsonObj:			
			for x in jsonObj['interviewersNames']:
				if x != "":
					userRulesInterviewersNames.append(x)	

		if 'interviewersSplits' in jsonObj:			
			for x in jsonObj['interviewersSplits']:
				if x != "":
					userRulesInterviewersSplits.append(x)		

					
			 
		if 'structureRegexPattern' in jsonObj:		
			userRulesSplitRegex  = jsonObj['structureRegexPattern']

		if 'publish' in jsonObj:		
			userPublish  = jsonObj['publish']
			
		if 'userName' in jsonObj:		
			userName  = jsonObj['userName']			
			
		if 'authorityControl' in jsonObj:		
			userAuthorityControl  = jsonObj['authorityControl']
			
			
		if 'structureIgnoreCountTest' in jsonObj:		
			if jsonObj['structureIgnoreCountTest'] == 'true':
				userRulesIgnoreCountTest  = True
			else:
				userRulesIgnoreCountTest  = False
			
		if 'partialAprovals' in jsonObj:		
			userRulespartialAprovals  = jsonObj['partialAprovals']		
		
				
		

	#############
	## User rules need to be outputed to a save file if they are global.	
	for x in userRulesIgnoreGlobal:  
		try:
			globalIgnore.index(x)
		except ValueError:
			f = open(dataDir + "globalIgnore.txt", 'a')
			f.writelines(x + "\n")
			f.close()		

	for x in userRulesOtherNames:  
		try:
			globalOther.index(x)
		except ValueError:
			f = open(dataDir + "globalOther.txt", 'a')
			f.writelines(x + "\n")
			f.close()		
			
	for x in userAuthorityControl:
		set = [x['name'], x['value']]
		try:
			globalAuthority.index(set)
		except ValueError:
			f = open(dataDir + "globalAuthority.txt", 'a')
			f.writelines(set[0] + ',' + set[1] + "\n")
			f.close()		
			
			
			
			#also write it to the notes file with any notes if it is a coined URI
			if 'sourceUrl' in x:
				f = open(dataDir + "globalAuthorityNotes.txt", 'a')
				f.writelines(set[1] + '|' + x['sourceUrl'] + '|' + x['sourceNotes'] + '|' + userName + "\n")
				f.close()	
			
			

	for x in userRulesSameAs:  	
			
		if len(x['org']) > 5:
		
			set = [x['org'],x['sameAs']]
			try:
				globalSameAs.index(set)
			except ValueError:
				f = open(dataDir + "globalSameAs.txt", 'a')
				f.writelines(strip_accents(set[0]) + ',' + strip_accents(set[1]) + "\n")
				f.close()		
			
	
	
	
	#overwrite/add in the authority
	for x in globalAuthority: 
		if x[0] != '' and x[1] != '':
			authorityNames[x[0]] = x[1]	
	for x in userAuthorityControl:
		if x['name'] != '' and x['value'] != '':	
			authorityNames[str(x['name'])] = str(x['value'])
	
	
	#add in the global sameAs
	for x in globalSameAs:
		userRulesSameAs.append({ 'org' : x[0], 'sameAs' : x[1]})
	
	#add in the global others
	for x in globalOther:
		if x not in userRulesOtherNames and x != '':
			userRulesOtherNames.append(x)
		
	
	
	activeStep = activeStep + 1
	updateProgress("Testing question split rules",fileName,activeStep)
	
	#This part tests diffrent regex splits on the whole doc, the idea is to find how the interview questions are split up based on known patterns
	#if it cannot determin how it is split it cannot continue, will need to add additonal patterns if differnt transcripts are added
	
	foundQuestionSplit = False
	splitType = ""
	
	#the number of splits that has to be inorder for the regex to be considered sucessful, this can vary based on the length of the interview
	splitThreshold = 100
	
	#realllly short interview here
	if len(entire_file) < 15000:
		splitThreshold = 20
	

	
	if (foundQuestionSplit==False) and userRulesSplitRegex != '':
		#user				
		splited = re.split(userRulesSplitRegex,entire_file)
		if (len(splited)>splitThreshold):
			foundQuestionSplit = True		
			splitPattern = userRulesSplitRegex
			splitType = "single"	
			
			
			if userRulesSplitRegex not in regexPatterns:
				#if the pattern worked, then we want to save it for future use			 
				f = open(dataDir + "regexPatterns.txt", 'a')
				f.writelines(userRulesSplitRegex + "\n")
				f.close()					
			
		else:
 
			
			if userRulesIgnoreCountTest == True:
				#if they force it accept but do not save it.
				foundQuestionSplit = True		
				splitPattern = userRulesSplitRegex
				splitType = "single"				
				
			
			else:
		
				print '{"results": {"error": true,"type": "Structure", "msg" : "That Pattern did not work. It only matched ' + str(len(splited)) + ' patterns. If this is a short interview consider using the Ignore count test option if you know it is correct.", "id": "' + fileMd5 + '"}}'
				sys.exit()
			

	#smithsonian
	if (foundQuestionSplit==False): 		
		splited = re.split(r'[\n]([A-Z][a-z]*:)',entire_file)	
		#print "smithsonian",len(splited)
		if (len(splited)>splitThreshold):
			foundQuestionSplit = True
			splitPattern = r'([A-Z][a-z]*:)'
			splitType = "lastname"
		
	if (foundQuestionSplit==False):
		#smithsonian 2
		splited = re.split(r'\n([A-Z]*:)\W',entire_file)	
		#print "hamilton",len(splited)
		if (len(splited)>splitThreshold):
			foundQuestionSplit = True		
			splitPattern = r'([A-Z]*:)'
			splitType = "lastname"	
	
	if (foundQuestionSplit==False):
		#hamilton
		splited = re.split(r'\W([A-Z]{2}:)\W',entire_file)	
		#print "hamilton",len(splited)
		if (len(splited)>splitThreshold):
			foundQuestionSplit = True		
			splitPattern = r'([A-Z]{2}:)'
			splitType = "initals"
			
			
			
			
	#try the saved regex patterns
	if (foundQuestionSplit==False):
	
		for x in regexPatterns:
			if x != '':
				splited = re.split(x,entire_file)
				if (len(splited)>splitThreshold):
					foundQuestionSplit = True		
					splitPattern = x
					splitType = "single"					
			
	
			
	#print splitPattern
	#print splitType
	#sys.exit()


	if (foundQuestionSplit==False):
		#print "Error: Could not split on the interview questions. Please update the regex split patterns."
		print '{"results": {"error": true,"type": "Structure", "msg" : "Need the regular expression split pattern", "id": "' + fileMd5 + '"}}'
		sys.exit()
	
	
	possibleSplits = []
	
	for x in splited:
		splitTest = re.split(splitPattern,x)
		
		if len(splitTest) > 1:
			
			if splitTest[1] not in possibleSplits:
				possibleSplits.append(splitTest[1])
	

	
	counter = -1
	#we can go two routes here, if there is already the NLP processed data for this file use that otherwise we need to process it	
	if os.path.exists(dataDir + fileMd5 + '.pkl'):

		activeStep = activeStep + 1
		updateProgress("Processing NLP data from saved file.", fileName,activeStep)
	
	
		pkl_file = open(dataDir + fileMd5 + '.pkl', 'rb')
		nlpData = pickle.load(pkl_file)
		pkl_file.close()
		
		
		
		for aSent in nlpData:
			#add the sentence into the expected allSentence var
			allSentences.append(aSent[0])
			counter = counter + 1
			#process the chunks
			#TODO: This code is repeated below, fix that...
			for chunk in aSent[1]:			
				
				if hasattr(chunk, 'node'):					
					if chunk.node == 'PERSON':					
						#check to see how long it is, one word names are not good
						if len(chunk.leaves()) == 1:						
							#save the partial matches for later
							if wordNotBlackListed(chunk.leaves()[0][0]):
								possiblePartials.append([chunk.leaves()[0][0], counter])
						else:
							name = ' '.join(c[0] for c in chunk.leaves())
							#make sure the word does not contain any of our black listed words						
							if wordNotBlackListed(name):
								if name not in foundNames:
									foundNames.append(name)										
					else:
						 
						word = ' '.join(c[0] for c in chunk.leaves())						
						if wordNotBlackListed(word):
							if [chunk.node,word] not in otherEnts:
								otherEnts.append([chunk.node,word])			

	else:
		
		activeStep = activeStep + 1
		updateProgress("Chunking sentences(will take a long time).",fileName,activeStep)	
		#print "tokenizing entire file"
		allSentences = nltk.sent_tokenize(entire_file)		
		#print "chunking sentences"	
		for sent in allSentences:
		
			counter = counter + 1
			allChunks = nltk.ne_chunk(nltk.pos_tag(nltk.word_tokenize(sent)))		
			#store
			nlpData.append([sent,allChunks])
		
			for chunk in allChunks:				
				 
				if hasattr(chunk, 'node'):					
					if chunk.node == 'PERSON':					
						#check to see how long it is, one word names are not good
						if len(chunk.leaves()) == 1:						
							#save the partial matches for later
							if wordNotBlackListed(chunk.leaves()[0][0]):
								possiblePartials.append([chunk.leaves()[0][0], counter])
						else:
							name = ' '.join(c[0] for c in chunk.leaves())
							#make sure the word does not contain any of our black listed words						
							if wordNotBlackListed(name):
								if name not in foundNames:
									foundNames.append(name)										
					else:
						word = ' '.join(c[0] for c in chunk.leaves())						
						if wordNotBlackListed(word):
							if [chunk.node,word] not in otherEnts:
								otherEnts.append([chunk.node,word])
		

		#store this data into the filesystem under the md5 of the file as filename. we can then reuse it next time.
		output = open(dataDir + fileMd5 + '.pkl', 'wb')
		pickle.dump(nlpData, output)
		output.close()
	
	 
	 
	

	activeStep = activeStep + 1
	updateProgress("Processing NLP found names",fileName,activeStep)	
	  
	for name in list(foundNames):	
		
		
		
		nameStriped = name

		nameStriped = nameStriped.replace(",","")
		nameStriped = nameStriped.replace("Jr.","Jr")				
		nameStriped = nameStriped.replace("Sr.","Sr")	
		
		if not authorityNames.has_key(nameStriped):
		
			#print "'" + name + "' Not found in the DBpedia name extract"
		 
		 	
			aName = name.split(' ')
			
			if not firstNames.has_key(aName[0]):
			
 				#print "\t" + aName[0] + " not found in the first name file"
 			
				if wordCorpus.has_key(aName[len(aName)-1].lower()) or wordCorpus.has_key(aName[len(aName)-1]):
					#print "\t" + name + " does not look like a real name to me. And it is not in the directory. And it contains a common word(" + aName[len(aName)-1] + ")."
					assumtions.append(name + " does not look like a real/jazz name to me. And it is not in the directory. And it contains a common word(" + aName[len(aName)-1] + "). Removing it from the name list. placinging it in the Other Entities")
					otherEnts.append(['?',name])
					#remove it from the found list
					del foundNames[foundNames.index(name)]
					
				else:
				
					#print "\t " + aName[len(aName)-1].lower() + " not in word corups"
					assumtions.append("Aussuming " + name + " is a real name.")
					#print "\tAussuming " + name + " is a real name."
					if name not in foundNames:
						foundNames.append(name)			
						namesNLP.append(name)			
					
			else:
				assumtions.append("Aussuming " + name + " is a real name.")
				#print "\tAussuming " + name + " is a real name."
				if name not in foundNames:
					foundNames.append(name)
					namesNLP.append(name)	
				
		
		
		else:
			namesMatched.append(name)
	


	
	
	#lets try to do the opposit, see if there are any names in our name list that maches any text in the document, but have to clean up the doc
	entire_file_clean = entire_file

	entire_file_clean = re.sub('[0-9]+', '', entire_file_clean)
	entire_file_clean = re.sub("\s\s+" , " ", entire_file_clean)
	entire_file_clean = entire_file_clean.replace("\n",' ')
	entire_file_clean = entire_file_clean.replace("\r",' ')
	entire_file_clean = entire_file_clean.replace("'s",'')	 
	entire_file_clean = entire_file_clean.translate(string.maketrans("",""), string.punctuation)
		

	activeStep = activeStep + 1
	updateProgress("Processing directory names",fileName,activeStep)			
	#print "Searching jazz directory names"	 
	for name, uri in authorityNames.iteritems():	
 		cleanName = name.translate(string.maketrans("",""), string.punctuation)	
		if name not in foundNames:
			if ' ' + cleanName + ' ' in entire_file_clean:
			
				if len(name.strip().split()) > 1:
					#print "Found " + name.strip()
					foundNames.append(name.strip())		 
					
	
	 
	#now we want to make sure that all the names are unique, not smaller part of larger name, this can happen if NLP gets a hit but not the full thing
	#was caught
	
	entire_semi_clean = entire_file

	#not cleaning all punctutaiton here, so kind of verbose
	entire_semi_clean = entire_semi_clean.replace("\n",' ')
	entire_semi_clean = entire_semi_clean.replace("\r",' ')
	entire_semi_clean = entire_semi_clean.replace("'s",' ')	 
	entire_semi_clean = entire_semi_clean.replace(",",' ').replace(".",' ').replace("[",' ').replace("]",' ').replace("?",' ').replace("!",' ').replace("<",' ').replace(">",' ').replace("\"",' ').replace("'",' ').replace(")",' ').replace("(",' ')
	
	#remove numbers
	entire_semi_clean = re.sub('[0-9]+', '', entire_semi_clean)
	#remove multiple spaces into one space
	entire_semi_clean = re.sub("\s\s+" , " ", entire_semi_clean)

	activeStep = activeStep + 1
	updateProgress("Running regex name rules",fileName,activeStep)				
	
	tmp = []
	for part in foundNames:	
		add = True
		for full in foundNames:
			if part in full and len(part) != len(full):			
				#print part, " looks like it is part of ", full
				assumtions.append(part + " looks like it is part of " + full + " removing the part from name list")
				add = False
							
		part = part.replace(".",' ')
		part = re.sub("\s\s+" , " ", part)

		
		#now we also want to make sure that the name acutally exists as a possibly human readable format in the file
		if " " + part + " " not in entire_semi_clean:
			#print part, " does not look like it acutally exitst in the text file"
			assumtions.append(part+" does not look like it acutally exitst in the text file, removing it from the name list")
			add = False
		
		
		if add:
			tmp.append(part)
	
	foundNames = list(tmp)
	 
	
	#a lot of transcripts will fill in partial names with something like [Jimmy] Carter, so look for that patern and replace them in the sentences
	#and also add it to the names list
	
	activeStep = activeStep + 1
	updateProgress("Finding [sic] patterns",fileName,activeStep)	
	for aSent in allSentences:
		  
		 
		regex = re.compile("([A-Z][a-z]*)\W\[([A-Z][a-z]*)\]")
		r = regex.search(aSent)
		if r:
			#print r.groups(), aSent
			aName = r.groups()[0] + ' ' + r.groups()[1]
			aNameOld = r.groups()[0].strip() + ' [' + r.groups()[1].strip() +']'
			
			#change it in this sentence
			id = allSentences.index(aSent)			
 			allSentences[id] = allSentences[id].replace(aNameOld,aName)
 			
			
			"""
			#change it in all the partial matches as well
			for partial in possiblePartials:
				id = possiblePartials.index(partial)
				if partial[1].find(aNameOld)!=-1:
					#print "looking for", aNameOld
					#print "before", possiblePartials[id][1]
					possiblePartials[id][1] = possiblePartials[id][1].replace(aNameOld,aName)
					#print "after", possiblePartials[id][1]
			"""
			
  			
			if aName not in foundNames:
				foundNames.append(aName)
				
				
	for aSent in allSentences:
		regex = re.compile("\[([A-Z][a-z]*)\]\W([A-Z][a-z]*)\W")
		r = regex.search(aSent)
		if r:
			#print r.groups(), aSent	
			aName = r.groups()[0] + ' ' + r.groups()[1]
			aNameOld = '['+ r.groups()[0].strip() + '] ' + r.groups()[1].strip()
			
			id = allSentences.index(aSent)			
 			allSentences[id] = allSentences[id].replace(aNameOld,aName)

			
			"""
			#print "looking for", aNameOld
			for partial in possiblePartials:
				if partial[1].find(aNameOld)!=-1:
					id = possiblePartials.index(partial)
					#print "before", possiblePartials[id][1]
					possiblePartials[id][1] = possiblePartials[id][1].replace(aNameOld,aName)
					#print "after", possiblePartials[id][1]
			"""		
				
			if aName not in foundNames:
				foundNames.append(aName)
			
	  
	#print foundNames,"\n\n"
	 
	

	activeStep = activeStep + 1
	updateProgress("Running clean up rules",fileName,activeStep)	
	#######################################
	#Clean up rules.
	#######################################
	
 	
	for i in range(0,len(allSentences)):
	
		##removes the hamilton line numbering
		
		allSentences[i] = re.sub("\n[0-9]*\W" , "\n", allSentences[i])
		allSentences[i] = re.sub(r'[0-9]*\s\s\s' , "", allSentences[i])
	 
		
		##remove the hamilton footer text
		allSentences[i] = re.sub(r'\(c\) Hamilton College Jazz Archive-[0-9]*-\n','',allSentences[i])
		allSentences[i] = re.sub(r'\(c\) Hamilton College Jazz Archive\s*-[0-9]*-\n','',allSentences[i])
	
		#smithsonnian footer
		allSentences[i] = re.sub(r'For additional information contact the Archives Center at 202.633.3270 or archivescenter@si.edu.*[0-9]*\n','',allSentences[i])
		allSentences[i] = re.sub(r'For additional information contact the Archives Center at 202.633.3270 or archivescenter@si.edu','',allSentences[i])		
		
	
	
		#try to remove random page numbers that gett littered around
		allSentences[i] = re.sub("\s[0-9]{2}\n" , "", allSentences[i])
	
	
		#... need to put this into a interface eventually
		allSentences[i] = re.sub(r'WILLIAMS.*[0-9]*\n','',allSentences[i])
		allSentences[i] = re.sub(r'\sWILLIAMS\n','',allSentences[i])
		
	 
	#for i in range(0,len(possiblePartials)-1):	
		#possiblePartials[i][1] = re.sub("\n[0-9]*\W" , "\n", possiblePartials[i][1])
		#possiblePartials[i][1] = re.sub(r'[0-9]*\s\s\s' , "", possiblePartials[i][1])
		
	
	#store an orginal copy
	allSentencesOrg = list(allSentences)	
	allSentencesHTML = list(allSentences)
	
 	
 	
	
			
	 
	
	#look at the >2 word names, make sure things did not bleed into eachother
	for aName in list(foundNames):
	
		if len(aName.split())>2:
			
			if entire_file.find(aName) == -1:
				 
				shortName = ' '.join(aName.split()[0:len(aName.split())-1])
				 
				if entire_file.find(shortName) != -1:
					#print "Could not find ", aName, 'but', shortName
					assumtions.append(aName + ' is not a real name, but a mashup of two lines, the real name is ' + shortName)
					
					del foundNames[foundNames.index(aName)]
					
					foundNames.append(shortName)
	
	 
	activeStep = activeStep + 1
	updateProgress("Finding nick name patterns",fileName,activeStep)	
	#a lot of musicans have the format First "Nickname" Last so see if we can  match that pattern
	for x in allSentences:
		x = re.sub('[0-9]+', '', x)
		x = re.sub("\s\s+" , " ", x)
		x = x.replace("\n",' ')
		x = x.replace("\r",' ')
		x = x.replace("'s",'')	 	
	
		
	 
		regex = re.compile('([A-Z][a-z]*)\W"([A-Z][a-z]*)"\W([A-Z][a-z]*)')
		r = regex.search(x)
		if r:
			 
			
			#add the vaious varations of their name to the found names 			
			
			#full
			aVaration = r.groups()[0] + ' ' + r.groups()[1] + ' ' + r.groups()[2]
 			if aVaration not in foundNames:
				foundNames.append(aVaration)			
				
			#add to known sameAs
			sameAs[aVaration] = r.groups()[0] + ' ' + r.groups()[2]
			
			#first + last
			aVaration = r.groups()[0] + ' ' + r.groups()[2]
 			if aVaration not in foundNames:
				foundNames.append(aVaration) 
			
			#nick  + last
			aVaration = r.groups()[1] + ' ' + r.groups()[2]
			if aVaration not in foundNames:
				foundNames.append(aVaration)

			#add to known sameAs
			sameAs[aVaration] = r.groups()[0] + ' ' + r.groups()[2]
			
			
			#nick name?
			#aVaration = r.groups()[1]
			#print aVaration
			  

	activeStep = activeStep + 1
	updateProgress("Finding names based on first name patterns",fileName,activeStep)
	#try to find any names that we did not match based on the first name file. Look for first names and see if it is a full name, and not in found names
	for x in allSentences:
		x = re.sub('[0-9]+', '', x)
		x = re.sub("\s\s+" , " ", x)
		x = x.replace("\n",' ')
		x = x.replace("\r",' ')
		x = x.replace("'s",'')	 
		x = x.replace("[",' ').replace("]",' ')
		#x = x.translate(string.maketrans("",""), string.punctuation)		
	
		for aFirstName in firstNamesFile:
			
			aFirstName = aFirstName.title()
			
			if x.find(aFirstName + ' ') != -1:				
				 
				#see if the next word is upper case
				x_split = x.split()
				
				
				#this error will popup if the name is part of a compound word, which we dont want
				try:
					x_index = x_split.index(aFirstName)
				except ValueError:
					continue
					
				
				if x_index + 1 <= len(x_split)-1:
					
					if x_split[x_index + 1].istitle():
						
						if len(x_split[x_index + 1]) > 1:
						
							x_possibleName = x_split[x_index] + ' ' + x_split[x_index + 1]
						
							x_possibleName = x_possibleName.translate(string.maketrans("",""), string.punctuation)
							
							if x_possibleName not in foundNames:
						
								#print x_possibleName, "|", x
								foundNames.append(x_possibleName)
				
			
	
	
	 
	
	
	
	
	activeStep = activeStep + 1
	updateProgress("Cleaning up found names",fileName,activeStep)	
	#filter out any names we just added whos last name is def not a name and not a other related name	
	for name in list(foundNames):	
		aName = name.split(' ')	
		if aName[len(aName)-1].lower() in notNameParts:
			del foundNames[foundNames.index(name)]
			
			
	allNameParts = []   
	allNamePartsLower = []
	
	
	#build a list of all parts of the found names
	for name in foundNames:	
		aName = name.split(' ')	
		for a in aName:
			allNameParts.append(a)
			allNamePartsLower.append(a.lower())		


	activeStep = activeStep + 1
	updateProgress("Identifying interview participants",fileName,activeStep)			
	
	############################################################### 
	#	Attempt to find the interviewers and interviewees
	#
	#now lets see if we can figure out the interviewee(s)	
	#first check the file name, if we can find the name there we are set
	
	
	formatedFileName = fileName.replace("_"," ").replace(","," ").replace("."," ")
	formatedFileName = re.sub("\s\s+" , " ", formatedFileName)
	
	for x in foundNames:
		if formatedFileName.lower().find(x.lower()) != -1:
			#print "Interviewee : ", x
			interviewees.append(x)
	
	interviewersRegex = re.compile("interviewed.*by\W(.*)\W")
	
	#now look through the sentences and see if there is keywords, only the first n seneteneces	
	for x in allSentences[0:20]:
		x = re.sub('[0-9]+', '', x)
		x = re.sub("\s\s+" , " ", x)
		x = x.replace("\n",' ')
		x = x.replace("\r",' ')
		x = x.replace("'s",'')	 
		x = x.translate(string.maketrans("",""), string.punctuation)	
		
		if x.lower().find('interviewee') != -1:
			
			useSentence = x.lower()
			
			#check for the other word, to make sure we are not processing thoese names too
			if useSentence.find('interviewer'):
				#okay so both words are in this sentence we need to cut out the parts not applicable
				if useSentence.find('interviewer') < useSentence.find('interviewee'):
					#it occurs before, so cut out everything until we get to the right word
					useSentence = useSentence[useSentence.find('interviewee'):]
				else:
					useSentence = useSentence[0:useSentence.find('interviewer')]
		
			for f in foundNames:
				if useSentence.find(f.lower()) != -1:
					#print "Interviewee : ", useSentence
					if f not in interviewees:
						interviewees.append(f)				
		
		
		if x.lower().find('interviewer') != -1:
		
			useSentence = x.lower()
			
			#check for the other word, to make sure we are not processing thoese names too
			if useSentence.find('interviewee'):
				#okay so both words are in this sentence we need to cut out the parts not applicable
				if useSentence.find('interviewee') < useSentence.find('interviewer'):
					#it occurs before, so cut out everything until we get to the right word
					useSentence = useSentence[useSentence.find('interviewer'):]
				else:
					useSentence = useSentence[0:useSentence.find('interviewee')]		
		
			for f in foundNames:
				if useSentence.find(f.lower()) != -1:
					#print "Interviewer : ", useSentence
					if f not in interviewers:
						interviewers.append(f)				
			
		
		if x.lower().find('interviewed by') != -1 or x.lower().find('interviewed in') != -1:			
			
 			for f in foundNames:
				if x.lower().find(f.lower()) != -1:
					if f not in interviewees and f not in interviewers:
						#print "Interviewer : ", x
						interviewers.append(f)

	
		
		#add a terminating space for some regex patterns  to match
		x = x + ' '
		
		r = interviewersRegex.search(x)
		
		if r != None:			
			if len(r.groups()) > 0:				
				if r.groups()[0] not in interviewees and r.groups()[0] not in interviewers:
					interviewers.append(r.groups()[0])
		


	##add in the user defined stuff if there
	for x in userRulesIntervieweesNames:
		if x not in interviewees:
			interviewees.append(x)

	for x in userRulesInterviewersNames:
		if x not in interviewers:
			interviewers.append(x)			

	#if the user supplied names they are probablly trying to fix something, make sure the auto detect did not mess up
	for x in userRulesIntervieweesNames:
		try:
			del interviewers[interviewers.index(x)]
		except:
			continue
	for x in userRulesInterviewersNames:
		try:
			del interviewees[interviewees.index(x)]
		except:
			continue	
			
	
	
	if len(interviewees) == 0:
		#print "Error: could not find the name of the person being interviewed"
		print '{"results": {"error": true,"type": "Structure", "msg" : "Need Interviewee names","id": "' + fileMd5 + '"}}'
 		sys.exit()

	if len(interviewers) == 0:
		#print "Error: could not find the name of the interviwer"		
		print '{"results": {"error": true,"type": "Structure", "msg" : "Need Interviewers names", "id": "' + fileMd5 + '"}}'
		sys.exit()
	
	
	#print "possibleSplits", possibleSplits
	#print "interviewees", interviewees
	#print "interviewers", interviewers
	

	intervieweesSplit = []
	interviewersSplit = []
	
	
	#if the user gave some splits, then don't just makeup our own
	if (len(userRulesIntervieweesSplits)==0):
		for x in interviewees:
			intervieweesSplit.append(x + ":")

	if (len(userRulesInterviewersSplits)==0):
			for x in interviewers:
				interviewersSplit.append(x + ":")		
	
	#user supplied data
	for x in userRulesIntervieweesSplits:
		if x not in intervieweesSplit:
			intervieweesSplit.append(x)
			

	for x in userRulesInterviewersSplits:
		if x not in interviewersSplit:
			interviewersSplit.append(x)			
	
 
	
	for x in possibleSplits:
	
	
		#check to see if this split is a interviewer/ee 

		for n in interviewees:			
			
			lastName = n.split()[len(n.split())-1] 			
			if x.find(lastName) != -1:
				if x not in intervieweesSplit:
					intervieweesSplit.append(x)
			
			firstName = n.split()[0] 			
			if x.find(firstName) != -1:
				if x not in intervieweesSplit:
					intervieweesSplit.append(x)			
	
		for n in interviewers:			
			lastName = n.split()[len(n.split())-1]
 			if x.find(lastName) != -1:
				if x not in interviewersSplit:
					interviewersSplit.append(x)			
	
			firstName = n.split()[0]
 			if x.find(firstName) != -1:
				if x not in interviewersSplit:
					interviewersSplit.append(x)		
					
		
		for n in interviewees:
 
			nameSplit = n.split()
			initals = ''
			for namePart in nameSplit:
				initals = initals + namePart[0]
				 
			
			if x.find(initals) != -1:
				if x not in intervieweesSplit:
					intervieweesSplit.append(x)
				
			#just the first and last
			initals = nameSplit[0] + nameSplit[len(nameSplit)-1]
			if x.find(lastName) != -1:
				if x not in interviewersSplit:
					interviewersSplit.append(x)						
					
		 
		 
		for n in interviewers:			
			nameSplit = n.split()
			initals = ''
			for namePart in nameSplit:
				initals = initals + namePart[0]
				
			if x.find(initals) != -1:
				if x not in interviewersSplit:
					interviewersSplit.append(x)
				
			#just the first and last
			initals = nameSplit[0] + nameSplit[len(nameSplit)-1]
			if x.find(lastName) != -1:
				if x not in interviewersSplit:
					interviewersSplit.append(x)						
				
	 

	#remove any names that match the split pattern for the interview ees or ers
	for a in list(foundNames):
		for x in intervieweesSplit:
			if x.find(a) != -1:
			
				if a not in interviewees:
					try:
						del foundNames[foundNames.index(a)]
						break
					except:
						continue				

		for x in interviewersSplit:
			if x.find(a) != -1:
				if a not in interviewers:
					try:
						del foundNames[foundNames.index(a)]
						break
					except:
						continue	
	
	
	
		
	
#	print "intervieweesSplit",intervieweesSplit
#	print "interviewersSplit",interviewersSplit
	
	

	activeStep = activeStep + 1
	updateProgress("Preparing partial name matches",fileName,activeStep)			
  
	#look for partial names in the sentences that contain part of a found name but not the full found name and is not already in the possiblePartials
	counter = -1
	for x in allSentences:
	
		#x_org = x
		counter = counter +1
	
		x = re.sub('[0-9]+', '', x)		
		x = x.replace("\n",' ')
		x = x.replace("\r",' ')
		x = x.replace("'s",'')	 
		x = x.replace("[",' ').replace("]",' ')
		x = re.sub("\s\s+" , " ", x)
		
		
		for aPart in allNameParts:
		
			#see if the partial is in the sentence/or at the end of the sentence
			if x.find(aPart + ' ') != -1 or x.find(aPart + '.') != -1 or x.find(aPart + ',') != -1 or x.find(aPart + '?') != -1 or x.find(aPart + '!') != -1:
				
				foundAPartial = True
				
				#see if a full version of this name is in the sentence
				for aFull in foundNames:
					 
					#is this partial part of this full name?
					if aFull.find(aPart) != -1:
	

						#The interviewer and interviewee names really pops up so much, do not consider it if it is one of them
						#this may create a possible problem where a partial of someone with the same name as the interview people, but 
						#it will cut out a large amount of noise so for now it is needed.
						if aFull in interviewees or aFull in interviewers:
							foundAPartial = False	
	 
						#is this full name in the sentence
						if x.find(aFull) != -1:
							
							#yes, do not prusue it as a patial
							foundAPartial = False
							

	
				if foundAPartial:
				
					#some limiting logic
					
					if len(aPart) > 1:
					
						
						#see if this partial is already in the possiblePartials						
						try:
							possiblePartials.index([aPart,counter])
						except ValueError:		
							#print aPart, "|", x							
							possiblePartials.append([aPart,counter]) 
						
						
				
	

	
	#before we look at partials make sure that there are no partials in the other entities, sometimes it confuses a single name for another type
 	 
	activeStep = activeStep + 1
	updateProgress("Processing Other Entities",fileName,activeStep)				 	
	#########################################
	#	Process the other Entities, see if they are names that we know about 
	
	#first see if the other ent is a name in the dbpedia lookup file
	for other in otherEnts:
		if authorityNames.has_key(other[1]):			
			if other[1] not in foundNames:
				foundNames.append(other[1])
				#print(other[1] + " is not a " + other[0] + " it is a name (found in dbpedia extract), adding it to the name list.")
				assumtions.append(other[1] + " is not a " + other[0] + " it is a name (found in dbpedia extract), adding it to the name list.")				
	
	
	
	#print otherEnts 
	
	tmp = []	
	for other in otherEnts:	
	
		if other[1].lower() in allNamePartsLower:
			#print other[1], " is a name, removing it from the Other Entites"
			assumtions.append(other[1] + " is not a " + other[0] + " it is a name, removing it from the Other Entites")
		else:
			if other[1] not in foundNames:
				tmp.append(other)		
			else:
				#print other[1], " is a name, removing it from the Other Entites"
				assumtions.append(other[1] + " is not a " + other[0] + " it is a name, removing it from the Other Entites")
				
	otherEnts = tmp
	
	#see if any of the otherEnts have first names in it
	for other in otherEnts:		
		otherName = other[1]		
		if len(otherName.split())>1:			
			otherName = otherName.split()[0]			
			if otherName.lower() in firstNamesFile:
				
				
				if other[0] == 'GPE':
					#print other[1], "is a location"
					continue
				
				
				#see if the "last name" is not possibly a name
 				if other[1].split()[len(other[1].split())-1].lower() in notNameParts:
					#print  other[1] ,"this is not a name."
					continue					
				
				
				#check to make sure the last word is not a common non name word
				
				#if other[1].split()[len(other[1].split())-1].lower() not in wordCorpusFile:			
				#print other[1],"looks like a persons name"
				assumtions.append(other[1] + " is not a " + other[0] + " it is a name, removing it from the Other Entites")
				foundNames.append(other[1])
			#else:
			#	print other[1],"on the border that it looks like a persons name, but no"
	
	 
	#filter out some obvious problem other entities
	for other in list(otherEnts):		
		if len(other[1]) < 4:
			del otherEnts[otherEnts.index(other)]
	 
	 
	 
	
	#print foundNames
	
	webNames = {}  
	webPartials = {}
	webMatches = {}
	webOther = {}
	
	 
	
	
	
	
	#############################################################################
	#Before we get to the matching remove any names that the user rules dictate
 	for x in list(foundNames):

	
		delIt = False
		
		try:
			userRulesIgnoreGlobal.index(x)
			delIt = True
		except ValueError:	
			delIt = delIt
		
		try:
			userRulesIgnoreLocal.index(x)
			delIt = True
		except ValueError:	
			delIt = delIt

		try:
			userRulesOtherNames.index(x)
			delIt = True
		except ValueError:	
			delIt = delIt
			
	  
		
		if delIt:
			
			del foundNames[foundNames.index(x)]
					
	
	
	#add in names
	for x in userRulesManualNames:
		if x not in foundNames:
			foundNames.append(x)	
			
	for x in userRulesSameAs:		
		if x['sameAs'] not in foundNames:
			foundNames.append(x['sameAs'])	
	 
	for x in userRulesIntervieweesNames:
		if x not in foundNames:
			foundNames.append(x)	
			
	for x in userRulesInterviewersNames:
		if x not in foundNames:
			foundNames.append(x)	
	

	activeStep = activeStep + 1
	updateProgress("Running levenshtein comparsions",fileName,activeStep)				 		
	#a lot of time typeos in names result in work to correct. so run a levenshtein distance on the two names, if they are seperateated by 1 diff 
	#then try to change it in all the document before we start matching
	levUseAry = []
	for a in foundNames:		
		for b in foundNames:		
			if a != b:				
				lev = levenshtein(a,b)
				if lev == 1:
					
					if entire_file.count(a) > entire_file.count(b):
						levUse = a
						levDel = b
					else:
						levUse = b
						levDel = a
					
					if [levUse,levDel] not in levUseAry:
						levUseAry.append([levUse,levDel])
				
	#print levUseAry				
	for i in range(0,len(allSentences)):	
		for lev in levUseAry:			
			allSentences[i] = allSentences[i].replace(lev[1],lev[0])
			allSentencesHTML[i]= allSentencesHTML[i].replace(lev[1],lev[0])
	
	
	for aName in globalIgnore:		
		for aFoundName in list(foundNames):			
			if aFoundName == aName:				
				del foundNames[foundNames.index(aFoundName)]
				
	 
	#populate the names web object real quick
	for a in foundNames:
		if authorityNames.has_key(a):
			
			webNames[a] = { "authority" : authorityNames[a] , "count" : 0}
			
		else:
			#print "No key for ", a	
			webNames[a] = { "authority" : '', "count" : 0}	
			
		
		if a in interviewees:
			webNames[a]['interviewee'] = True

		if a in interviewers:
			webNames[a]['interviewer'] = True		


	

			
			
	
	###########################################################################3
	#	Match Other entites
	#			
	activeStep = activeStep + 1
	updateProgress("Matching Other Entities",fileName,activeStep)	
	
	#if a othername was samed as get the orginal into the othername array
	for aSameAs in userRulesSameAs:		
		if aSameAs['sameAs'] in userRulesOtherNames:
			userRulesOtherNames.append(aSameAs['org'])
			
	
	#remove any other ent that has a known name in it
	for aOther in list(otherEnts):
		if aOther[1] in foundNames:
			del otherEnts[otherEnts.index([aOther[0],aOther[1]])]
		
	
	allEnts = []
	for aOther in otherEnts:
		allEnts.append(aOther[1])
	
	#if there are any confirmed other names add them to the other ents
	#if they are alreaddy in there mark them as confirmed

	for x in userRulesOtherNames:		
	
		if x not in allEnts:
			
			otherEnts.append(['confirmed',x])
		else:			
			for aOther in otherEnts:				
				if aOther[1] == x:
					otherEnts[otherEnts.index([aOther[0],aOther[1]])][0] = 'confirmed'
					
			
	
	otherCounter = 0
	
	#an other ent cannot be part of the split patterns or it break the split process
	for aOther in list(otherEnts):	
		for x in interviewersSplit:
			if x.lower().find(aOther[1].lower()) != -1:
				try:
					del otherEnts[otherEnts.index([aOther[0],aOther[1]])]
				except:
					continue
				
		for x in intervieweesSplit:
			if x.lower().find(aOther[1].lower()) != -1:
				try:
					del otherEnts[otherEnts.index([aOther[0],aOther[1]])]
				except:
					continue
	
	
	
	for i in range(0,len(allSentences)):
		
		for aOther in otherEnts:
		
			otherCounter = otherCounter + 1
			otherId = 'other_'+ str(i) + '_' + str(otherCounter) 

			#sent_clean = re.sub('[0-9]+', '', allSentences[i])
			sent_clean = re.sub("\s\s+" , " ", allSentences[i])
			sent_clean = sent_clean.replace("\n",' ')
			sent_clean = sent_clean.replace("\r",' ')
			sent_clean = sent_clean.replace("'s",'')				
			
			if aOther[1] in sent_clean:

				if allSentencesHTML[i].count(aOther[1]) != 0:
					allSentencesHTML[i] = allSentencesHTML[i].replace(aOther[1], '<span class="otherMatch" rel="popover" id="' + otherId + '">' + aOther[1] +'</span>',1)							
				else:
					
					
					#try to plurize the first term
					newOtherEntName = aOther[1].split()[0] + "'s " + ' '.join(aOther[1].split()[1:])
					allSentencesHTML[i] = allSentencesHTML[i].replace(newOtherEntName, '<span class="otherMatch" rel="popover" id="' + otherId + '">' + newOtherEntName +'</span>',1)							
					
					#try line break
					newOtherEntName = aOther[1].split()[0] + "\n" + ' '.join(aOther[1].split()[1:])
					allSentencesHTML[i] = allSentencesHTML[i].replace(newOtherEntName, '<span class="otherMatch" rel="popover" id="' + otherId + '">' + newOtherEntName +'</span>',1)							
				
				confirmed = False
				if aOther[0] == 'confirmed':
					confirmed = True
					
				webOther[otherId] = { 'name': aOther[1], 'sentenceNumber': i, 'confirmed' : confirmed}

			
	activeStep = activeStep + 1
	updateProgress("Partial name matching",fileName,activeStep)			
	
	###########################################################################3
	#	Start partial matching
	#
	
	
	#remove any partial that is just picking up interview transcript identifyer
	for partial in list(possiblePartials):		
		#if partial[1][0:len(partial[0])+1] == partial[0] + ':':
		if allSentences[partial[1]][0:len(partial[0])+1] == partial[0] + ':':
			del possiblePartials[possiblePartials.index(partial)]
 	
 

	#print allSentencesOrg[len(allSentencesOrg)-8:]
	partialCounter = 0
	for partial in possiblePartials:
		
		partialCounter=partialCounter+1
		
		
		
		
		if partial[0] in allNameParts:
		 
			
			matchedFullNames = []
			
			#find out how many possible names this parital could match
			for name in foundNames:	
				aName = name.split(' ')	
				for a in aName:
					if a == partial[0]:
						if name not in matchedFullNames:
							matchedFullNames.append(name)
			
			
			 
			#now see if any of these possible full names are already in the sentence, if so we can disregard this partial match
			
			sent_clean = re.sub('[0-9]+', '', allSentences[partial[1]])
			sent_clean = re.sub("\s\s+" , " ", sent_clean)
			sent_clean = sent_clean.replace("\n",' ')
			sent_clean = sent_clean.replace("\r",' ')
			sent_clean = sent_clean.replace("'s",'')	 		
			
			#if partial[0] == 'Antonio' or partial[0] == 'Heart':
			#	print allSentences[partial[1]]
			#	print sent_clean
			
			#run it a number of times to make sure we strip out any full names that are matching the partials			
			for i in range(10):
				for matched in list(matchedFullNames):
					if matched in sent_clean:
						sent_clean = sent_clean.replace(matched,"")

						
			#if partial[0] == 'Antonio' or partial[0] == 'Heart':
			#	print sent_clean						
						
						
			#Is the partial still even in the cleaned sentence?			
			
			if partial[0] in sent_clean:

			
				foundInContext = False
				#print "'" + partial[0] + "' Might be a partial. | ", sent_clean, matchedFullNames 
				
				##check all to make sure the name was mentioned recently
				if len(matchedFullNames) > 0:
				
				
					#it has more than one then we need to figure out which to use
					#loop back 20 sentences and see if we can find the full name
					
 					startSentence = partial[1]
					endSentence = startSentence - 20
					if endSentence < 0:
						endSentence = 0
						
					matchedBeforeFull = []	
						
					for i in reversed(xrange(endSentence,startSentence)):
						 

						sent_clean_before = re.sub('[0-9]+', '', allSentences[i])
						sent_clean_before = re.sub("\s\s+" , " ", sent_clean_before)
						sent_clean_before = sent_clean_before.replace("\n",' ')
						sent_clean_before = sent_clean_before.replace("\r",' ')
						sent_clean_before = sent_clean_before.replace("'s",'')							
					
						#if partial[0] == 'Mike':
						#	print "searching:", allSentences[i]
				
						for aMatchedFull in matchedFullNames:							
							if aMatchedFull in sent_clean_before:
								#if partial[0] == 'Mike':
								#	print "Found " + aMatchedFull + " at sentence #" + str(i) + '{' + allSentences[i] + '}'
								if aMatchedFull not in matchedBeforeFull:
									matchedBeforeFull.append(aMatchedFull)

									
									
					#if no luck with that try 3 sentences ahead, somethimes they intitalize with a first name, then clarify
					if len(matchedBeforeFull) == 0:
						
						startSentence = partial[1]
						endSentence = startSentence + 3
						if endSentence > len(allSentences):
							endSentence = len(allSentences)						
						
						for i in xrange(startSentence, endSentence):
						
							sent_clean_before = re.sub('[0-9]+', '', allSentences[i])
							sent_clean_before = re.sub("\s\s+" , " ", sent_clean_before)
							sent_clean_before = sent_clean_before.replace("\n",' ')
							sent_clean_before = sent_clean_before.replace("\r",' ')
							sent_clean_before = sent_clean_before.replace("'s",'')								

							for aMatchedFull in matchedFullNames:
								if aMatchedFull in sent_clean_before:
									#print "Found " + aMatchedFull + " at sentence #" + str(i) + '{' + allSentences[i] + '}'
									if aMatchedFull not in matchedBeforeFull:
										matchedBeforeFull.append(aMatchedFull)							




					#if partial[0] == 'Mike':
					#	print "sentence:",allSentences[partial[1]]
					#	print "matchedBeforeFull", matchedBeforeFull
										
					#if there was only one hit, go with that one
					if len(matchedBeforeFull) == 1:
						matchedFullNames = matchedBeforeFull
						foundInContext = True
					#else:
					#	print "Could not match because I found more than one (or zero) possible partial name match", matchedBeforeFull
 			
				
				
				#eval the user rules, if the possible match has multiple names, they are pointing to the same name, remove the sameAs name from
				#the possiblities				
				for x in list(matchedFullNames):
				
					for y in userRulesSameAs:
					
						if x == y['org']:
							#a possible match is in the same as file
							 
							#make sure the correct name is in the possiblities
							if y['sameAs'] not in matchedFullNames:
								matchedFullNames.append(y['sameAs'])
							
							#drop the old name out of the possiblities
							try:
								del matchedFullNames[matchedFullNames.index(y['org'])]
							except:
								continue
 				
				
				partialId = 'partial_'+ str(i) + '_' + str(partialCounter) 
				orgSent = allSentences[i].replace('"',"'")
				 

				partialAprovalsPass = False
				#if this partial has been curated by the user?				
				for aProval in userRulespartialAprovals:							
					if aProval['sentenceNumber'] == str(partial[1]) and partial[0] == aProval['partial']:
					
						if aProval['use'] == 'ignore':
							partialAprovalsPass	= True
							continue
						
						else:
							allSentences[partial[1]] = allSentences[partial[1]].replace(partial[0],aProval['use']) 
							allSentencesHTML[partial[1]] = allSentencesHTML[partial[1]].replace(partial[0],aProval['use']) 
						
							partialAprovalsPass	= True
							#print aProval						
							#print allSentences[i]
							continue
				
				if (partialAprovalsPass==True):
					continue
				
						
				if len(matchedFullNames)==1:
					 
					
					useName = matchedFullNames[0]
					
					if useName in sent_clean:
						continue
					
					
					
					#some times the partial is a two part partial (...yeah) 
					#something like "Nat Cole" for "Nat King Cole" happens, it still a partial, just a two word partial
					#this really messes up the sentence because then multiple partials ar detected 
					if len(useName.split()) > 2:
					
 						if useName.split()[0] + ' ' + useName.split()[2] in sent_clean:
							partial[0] = useName.split()[0] + ' ' + useName.split()[2]
					
				
					#do not match if we are about to match it to an interviewer or interviewee
					if matchedFullNames[0] in interviewees or matchedFullNames[0] in interviewers:
						continue
				
					
					
					#if this name is mapped to a sameAs name, use the same as
				
				
					i = partial[1]
					
					
					#check to see if the full name is in the sentence. this can happen a lot if they intitaly refer to the person's full name and their first name
					#if so just skip it because we know who they are talking about and that sentence will be flaged with the full name
					if useName in allSentences[i]:
						continue

						 

					#if the name was found recently then consider it a full match, otherwise flag it for review
					if (foundInContext):
					
						
						allSentences[i] = allSentences[i].replace(partial[0],useName) 
						allSentencesHTML[i] = allSentencesHTML[i].replace(partial[0], '<span class="partialMatch" rel="popover" id="' + partialId + '">' + useName +'</span>',1)							
						webPartials[partialId] = { 'singleMatch' : True,  'partial' : partial[0], 'full' : [useName], 'sentenceNumber': i}
						
					else:
					
						allSentencesHTML[i] = allSentencesHTML[i].replace(partial[0], '<span class="partialMatch" rel="popover" id="' + partialId + '">' + partial[0] +'</span>',1)							
						webPartials[partialId] = { 'singleMatch' : False, 'partial' : partial[0], 'full' : [useName], 'sentenceNumber': i}
					
						
					
					
					#	print partial[0], 'single=', matchedFullNames, len(partial[0].split()), allSentences[i]
												
				elif len(matchedFullNames)>1:
					

					i = partial[1]
						
					allSentencesHTML[i] = allSentencesHTML[i].replace(partial[0], '<span class="partialMatch" rel="popover" id="' + partialId + '">' + partial[0]+'</span>', 1)
					webPartials[partialId] = { 'singleMatch' : False, 'partial' : partial[0], 'full' : matchedFullNames,  'sentenceNumber': i}
					
					#print partial[0], 'many=', matchedFullNames, len(partial[0].split()), allSentences[i]
					
				 
							
	
							
							
							
	
		
							
	activeStep = activeStep + 1
	updateProgress("Producing HTML coded transcript",fileName,activeStep)			
	#acutally locate the names in the sentences and tage them for the HTML
	
	matchCounter = 0
	
	for i in range(0,len(allSentences)):
		sent_clean = re.sub('[0-9]+', '', allSentences[i])
		sent_clean = re.sub("\s\s+" , " ", sent_clean)
		sent_clean = sent_clean.replace("\n",' ')
		sent_clean = sent_clean.replace("\r",' ')
		sent_clean = sent_clean.replace("'s",'')			

		for aName in foundNames:
		
			
		
			if sent_clean.find(aName) != -1:





			
				aNameStart = aName.split()[0]
				aNameEnd = aName.split()[len(aName.split())-1]
			
				aNameStartReplace = aNameStart
				aNameEndReplace  = aNameEnd
		 
				#make sure it is not a partial, 
				if allSentencesHTML[i].find(aNameEnd + "</span>") == -1:


					
					matchCounter = matchCounter + 1
					matchId = "match_" + str(matchCounter)
						
					#is the name a sameAs? point it to the correct name
					for y in userRulesSameAs:		
					
						if aName == y['org']:							 
							aName = y['sameAs']
							aNameStartReplace = aName.split()[0]
							aNameEndReplace = aName.split()[len(aName.split())-1]
							#replace them in the sentence so it picks up below
							allSentencesHTML[i] = allSentencesHTML[i].replace(y['org'],y['sameAs'],1)
							allSentences[i] = allSentencesHTML[i].replace(y['org'],y['sameAs'],1)
 							 	
					
					#if this is the interviewer? they should not be in the name lookup, but it could happen
					if (aName in interviewers):
						continue
					
					

					#if (aName in interviewees):
						
						#if there is only one interviewee, we do not want to tag him in the text
					#	if len(interviewees) == 1:
							
							#skip it if it looks like they are using the full name as a split pattern
					#		if allSentencesHTML[i].find(aName + ":") != -1:
					#			continue
					
					

					#is this a othername?
					if aName in userRulesOtherNames:
						continue
								
						
					#if we find the whole name, just replace the entire thhing
					if allSentencesHTML[i].find(aName) != -1:
						allSentencesHTML[i] = allSentencesHTML[i].replace(aName, '<span class="fullMatch" id="' + matchId + '">'+aName+"</span>", 1)
					
					
					else:
					 
						#TODO If the name is repeeated multiple times in the same sentence it can mess up placement of the <span>s below
						#if allSentencesHTML[i].count(aNameStart) > 1:
						#	print "looking for ", aNameStart, allSentencesHTML[i]
						
						
						
						allSentencesHTML[i] = allSentencesHTML[i].replace(aNameStart, '<span class="fullMatch" id="' + matchId + '">'+aNameStartReplace, 1)
						allSentencesHTML[i] = allSentencesHTML[i].replace(aNameEnd, aNameEndReplace+'</span>', 1)
						#print webNames[aName]
					
					
					webNames[aName]['count'] = webNames[aName]['count'] + 1						
					webMatches[matchId] = { 'name': aName, 'sentenceNumber': i }
					
					#verify that we are closing everything we opened
					missingClosingTags = allSentencesHTML[i].count("<span") - allSentencesHTML[i].count("</span")
					if  missingClosingTags > 0:
					
						
						
						for z in range(0,missingClosingTags):
							allSentencesHTML[i] = allSentencesHTML[i] + "</span>"
						
					
	 
	sameAsLookup = []
		 
	##remove the sameAs name from the webObj, we don't want them showing up
	for y in userRulesSameAs:
	
		sameAsLookup.append(y['sameAs'])
		
		try:
			del webNames[y['org']]
		except:
			continue
		

	
	
	
	for key, value in webNames.items(): 
	
		if value['count'] == 0 and key not in userRulesManualNames and key not in sameAsLookup:
			try:
				del webNames[key]
			except:
				continue	
	
		
		#if it is just a sameas rule and zero count, delete it
		if value['count'] == 0 and key in sameAsLookup:
			try:
				del webNames[key]
			except:
				continue	
		
		if key in userRulesOtherNames:
			try:
				del webNames[key]
			except:
				continue	
		 
	
	bufferOrg = ""
	buffer = ""
	questions = []
	answers = []
	
	
	foundNames = []
	#rebuild the found names based on the rules that have filtered out the non-sucessful names
	for key, value in webNames.items(): 	
		foundNames.append(key)
 
					
	
	 
	activeStep = activeStep + 1
	updateProgress("Spliting into metastructures",fileName,activeStep)				 
	allSentencesHTML[0] = '<div class="transcriptHeader">' + allSentencesHTML[0]
	splitCounter = 0
	all = ""
	output = open(dataDir + fileMd5 + '.txt', 'w')
	 
	
	
	for i in range(0,len(allSentences)):	
	
 
		for x in intervieweesSplit:
			if allSentencesHTML[i].find(x) != -1:
				allSentencesHTML[i] = allSentencesHTML[i].replace(x,'</div>\n<div class="interviewee"><b>[A]'+x+'</b>')
				splitCounter=splitCounter+1
				
			 

		for x in interviewersSplit:
			if allSentencesHTML[i].find(x) != -1:
				allSentencesHTML[i] = allSentencesHTML[i].replace(x,'</div>\n<div class="interviewer"><b>[Q]'+x+'</b>')				
				splitCounter=splitCounter+1
			 
		 
		#this is a shitty work around to get decently formated final text beacuse NLTK tokenizer is striping out \n on some sentences
		#so it basicly checks to see if it should have a \n based on the orginal text, errors can occure, but its not too bad
		try:
			if allSentences[i] != '':
				if len(entire_file.split(allSentences[i])) > 1:			
					if entire_file.split(allSentences[i])[1][0] == "\n":				
						if allSentences[i][len(allSentences[i])-1] != "\n":
							output.writelines(allSentencesHTML[i] + "\n")				
						else:
							output.writelines(allSentencesHTML[i]+" ")					
					else:
						output.writelines(allSentencesHTML[i]+" ")
				else:
					output.writelines(allSentencesHTML[i]+" ")				
			else:
				output.writelines(allSentencesHTML[i]+" ")			
		except:
			output.writelines(allSentencesHTML[i]+" ")			
		
	if splitCounter < 10:
			print '{"results": {"error": true,"type": "Structure", "msg" : "Need interviwee/interviewer Split text, could not assign roles to text.", "id": "' + fileMd5 + '"}}'
			sys.exit()
		
	
	
	activeStep = activeStep + 1
	updateProgress("Writing output data",fileName,activeStep)				

	#print json.dumps(webNames)
	#print json.dumps(webPartials)
	#print json.dumps(webMatches)

	d = json.dumps(webNames)
	f = open(dataDir + fileMd5 + '_names.json', 'w')
	f.write(d + "\n")
	f.close()

	d = json.dumps(webPartials)
	f = open(dataDir + fileMd5 + '_partials.json', 'w')
	f.write(d + "\n")
	f.close()	

	d = json.dumps(webMatches)
	f = open(dataDir + fileMd5 + '_matches.json', 'w')
	f.write(d + "\n")
	f.close()		
	
	d = json.dumps(webOther)
	f = open(dataDir + fileMd5 + '_others.json', 'w')
	f.write(d + "\n")
	f.close()		
	
	
	#output the key the web app will use to reference the files
	print '{"results": {"error": false,"id": "' + fileMd5 + '"}}'
	
	
	#remove the user rule file
#	if os.path.exists(dataDir + fileMd5 + '_userRules.json'):
#		os.remove(dataDir + fileMd5 + '_userRules.json')


	
	#userPublish['publish'] = 1
	#userPublish['intervieweeAuth'] = ""
 

	#publish results?!?!
 	if (userPublish.has_key('publish')):
		
		if int(userPublish['publish']) == 1:
		
			
			debugFile = open('debug.txt', 'w')
		
 			publishInterviewees = []
			publishInterviewers = []
			
			for key, value in webNames.items():
			
				if 'interviewee' in webNames[key] and webNames[key]['authority'] != userPublish['intervieweeAuth']:
					publishInterviewees.append(key)
					
				if 'interviewer' in webNames[key]:
					publishInterviewers.append(key)			
			
				if len(publishInterviewers) == 0:
					publishInterviewers = userRulesInterviewersNames
			
			
			publishInterviewees = ', '.join(publishInterviewees)
			publishInterviewers = ', '.join(publishInterviewers)

			
			allText = ''	
			for x in allSentencesOrg:
				allText = allText + x + "\n"


 			splited = re.split("(" + splitPattern + ")",allText)
				
			debugFile.write(splitPattern)
			
				
			allTextMod = ''
			for x in allSentences:
				allTextMod = allTextMod + x + "\n"		
			
			splitedMod = re.split("(" + splitPattern + ")",allTextMod)
			

			
			#print "Split Pattern:",splitPattern
			
			"""
			c = 0
			for w in  splited:
				print c, w
				print c, splitedMod[c]
			 	c= c + 1
			 
			sys.exit()
			"""

			
			#print len(splited),len(splitedMod)
			
			 
			#delete the header
			del splited[0]
			del splitedMod[0]
			 
			 
			all = []
			allAuths = []
			counter = -1
			
			"""
			for i in range(0,len(splited)-1):
				
				print i, "Org:", splited[i]
				print i, "Mod:", splitedMod[i]
				print "------"
			
			
			
			sys.exit()
			 
			"""
			
			for i in range(0,len(splited)-1):
				
				
				
				
				for x in intervieweesSplit:
				
					
					#try:
					
					if splited[i].find(x) != -1:
										
						if splited[i+1] != splited[i]:
						
							clean = splitedMod[i+1].replace("\n", " ")
							counter = counter + 1
							
							
							try:
								names = checkForNamesFinal(splitedMod[i+1],foundNames,authorityNames,interviewers,interviewees)
									
								#print counter, splitedMod[i], "---|---", splitedMod[i+1]
								#print "\t", names								
								
								
								
							except IndexError:
								names = checkForNamesFinal("",foundNames,authorityNames,interviewers,interviewees)
							
								#print "ERROR:", names 
							
							#answers.append((clean,names,counter,'A'))
							 
							
 
							for aAuth in names:
								
								answers.append((fileMd5,counter,aAuth,'A',splitedMod[i].replace(':','').strip()))
								
								#if counter > 700 and counter < 725:
								#	print "answers:", (fileMd5,counter,aAuth,'A')
								
								if aAuth not in allAuths:
									allAuths.append(aAuth)
								
							
							#debug.write(answers)
							#if counter > 700 and counter < 725:
							#	print "all:", (fileMd5,clean,counter,'A')

							all.append((fileMd5,clean,counter,'A',splitedMod[i].replace(':','').strip()))
							
 							
							break
				
					#except IndexError:
						
					#	continue	
							
				for x in interviewersSplit:
					
					
					
					#try:	
						
					if splited[i].find(x) != -1:
						 
						if splited[i+1] != splited[i]:


						
							clean = splited[i+1].replace("\n", " ")
							counter = counter + 1
							
							debugFile.write(str(i+1)  + "\n")
							debugFile.write(splited[i+1]  + "\n")
														
							try:
								names = checkForNamesFinal(splitedMod[i+1],foundNames,authorityNames,interviewers,interviewees)
								
								
									
								#print counter, splitedMod[i], splitedMod[i+1]
								#print "\t", names	
								
								
							except IndexError:
								names = checkForNamesFinal("",foundNames,authorityNames,interviewers,interviewees)

							
							for aAuth in names:
								questions.append((fileMd5,counter,aAuth,'Q',splitedMod[i].replace(':','').strip()))	
								if aAuth not in allAuths:
									allAuths.append(aAuth)		
									
															
															
							#HACK 3/6/13 FIX								
							try:
								all.append((fileMd5,clean,counter,'Q',splitedMod[i].replace(':','').strip()))
							except IndexError:
								#print clean
								all.append((fileMd5,clean,counter,'Q',clean))
							break	
							
					#except IndexError:
						
					#	continue		
				
		
			"""
			acount = 0
			
			for qqq in answers:
				print acount, qqq
				acount = acount + 1
				
			print "-----------------------"
			
			acount = 0
			
			for qqq in all:
				print acount, qqq
				acount = acount + 1
				
							
			print "Errrrr"
			
			sys.exit()
			"""
			 
			
			import MySQLdb
			db=MySQLdb.connect(user="",passwd="",db="")
			

			#Remove the stored transcript info
			c=db.cursor() 
			c.execute("""DELETE FROM transcripts where `md5` = %s""",fileMd5)
			c.close()	
			
			#store transcript info
			c=db.cursor() 
			if userPublish['intervieweeAuth'].find('<')==-1:
				userPublish['intervieweeAuth'] = '<' + userPublish['intervieweeAuth'] + '>'
			c.execute("""INSERT INTO transcripts (`md5`, `sourceName`, `sourceURL`, `interviewee`, `intervieweeURI`, `interviewers`, `interviewees`) VALUES (%s, %s, %s, %s, %s, %s, %s)""",(fileMd5,userPublish['sourceName'],userPublish['sourceURL'],userPublish['interviewee'],userPublish['intervieweeAuth'],publishInterviewers,publishInterviewees))	
			c.close()	
			
			
			#grab the authorites stored and add in any new ones
			c=db.cursor()
			c.execute("""SELECT `uri` FROM `authority`""")
			dbNamesTup = c.fetchall()	
			c.close()
			dbNames	=[]
			for aDb in dbNamesTup:
				dbNames.append(aDb[0])
			 
			for aName in foundNames:		
				if authorityNames.has_key(aName):
					if authorityNames[aName] not in dbNames:
						
						
						 
						sourceUrl=''
						sourceNotes = ''
						author = 'none'
						for x in globalAuthorityNotes:
						
							if (x[0] == authorityNames[aName]):
								

								try:
									sourceUrl = x[1]
								except IndexError:
									sourceUrl = "??"							

								try:
									sourceNotes = x[2]
								except IndexError:
									sourceNotes = "??"
								
								
								try:
									author = x[3]
								except IndexError:
									author = "??"
						
		 
						c=db.cursor()
						c.execute("""INSERT INTO `authority` (`name`,`uri`, `coinUri`, `coinInfo`, `author`, `image`) VALUES (%s, %s, %s, %s, %s, '')""",(aName,authorityNames[aName],sourceUrl,sourceNotes,userName))
						c.close()
				
			
			
			#other entites
			#Remove any stored matches
			c=db.cursor() 
			c.execute("""DELETE FROM other where `personURI` = %s""",userPublish['intervieweeAuth'])
			c.close()	
			
			for key, value in webOther.items():
				if value['confirmed'] == True:
					c=db.cursor()
					c.execute("""INSERT INTO `other` (`name`,`personURI`) VALUES (%s, %s)""",(value['name'],userPublish['intervieweeAuth']))
					c.close()				
 			  
			#Remove any store questions/anwers for this transcript
			c=db.cursor() 
			c.execute("""DELETE FROM text where transcript = %s""",fileMd5)
			c.close()	
			
			#Store the questions and answers text
			c=db.cursor()	
			c.executemany("""INSERT INTO `text` (`transcript`, `text`, `idLocal`, `type`, `speaker`) VALUES (%s, %s, %s, %s, %s)""",all)	
			c.close()
			
			#Store the match stats
			c=db.cursor() 
			c.execute("""REPLACE INTO cs_transcripts SET totalPairs = %s, `transcript` = %s""",(len(answers) + len(questions),fileMd5))
			c.close()			
			
			
			
			#Remove any stored matches
			c=db.cursor() 
			c.execute("""DELETE FROM matches where transcript = %s""",fileMd5)
			c.close()		
			
			#Store the answers
			c=db.cursor()	
			c.executemany("""INSERT INTO `matches` (`transcript`, `idLocal`, `personURI`, `type`, `speaker`) VALUES (%s, %s, %s, %s, %s)""",answers)	
			c.close()	

			#Store the questions
			c=db.cursor()	
			c.executemany("""INSERT INTO `matches` (`transcript`, `idLocal`, `personURI`, `type`, `speaker`) VALUES (%s, %s, %s, %s, %s)""",questions)	
			c.close()	
			
			
			
			db.commit()
			db.close()
			
			
			f = open(dataDir + "publishedFileNames.txt", 'a')
			f.writelines(fileNameOrg + "\n")
			f.close()			
			
		
			debugFile.close()


	
	
	if os.path.exists(fileName + '_status.json'):
		os.remove(fileName + '_status.json')
	

	
def checkForNamesFinal(text,foundNames,authorityNames,interviewers,interviewees):

		sent_clean = re.sub('[0-9]+', '', text)
		sent_clean = re.sub("\s\s+" , " ", sent_clean)
		sent_clean = sent_clean.replace("\n",' ')
		sent_clean = sent_clean.replace("\r",' ')
		sent_clean = sent_clean.replace("'s",'')			

		nameList = []
		
		for aName in foundNames:
			if authorityNames.has_key(aName) and aName not in interviewers and aName not in interviewees:
				if sent_clean.find(aName) != -1:
					if authorityNames[aName] not in nameList:
						nameList.append(authorityNames[aName])
					
				
				
		return nameList
				
	 
		
def updateProgress(msg,id,activeStep):
		
		f = open(id + "_status.json", 'w')
		f.writelines('{"results": {"msg": "' + msg + '", "step" : ' + str(activeStep) + ', "total" : ' +  str(totalSteps) + '}}')
		f.close()
	

def wordNotBlackListed(word):

	add = True

	for blackWord in blacklist:						
		if word.lower().find(blackWord) != -1:		
			add=False
			#print add

	return add
		
def formatName(name):
	#decode it to get rid of URL char codes 
	name = urllib.unquote(name)
	#stop at the first pranathesis, so we dont get things like (jazz_player)
	name = name[0:name.rfind('(')]
	name = name.replace('_',' ').replace('>','').strip()
	return name
		


def levenshtein(s1, s2):
    if len(s1) < len(s2):
        return levenshtein(s2, s1)
    if not s1:
        return len(s2)
 
    previous_row = xrange(len(s2) + 1)
    for i, c1 in enumerate(s1):
        current_row = [i + 1]
        for j, c2 in enumerate(s2):
            insertions = previous_row[j + 1] + 1 # j+1 instead of j since previous_row and current_row are one character longer
            deletions = current_row[j] + 1       # than s2
            substitutions = previous_row[j] + (c1 != c2)
            current_row.append(min(insertions, deletions, substitutions))
        previous_row = current_row
 
    return previous_row[-1]


def strip_accents(s):
   return ''.join((c for c in unicodedata.normalize('NFD', s) if unicodedata.category(c) != 'Mn'))

	
if __name__ == '__main__':
        main()

